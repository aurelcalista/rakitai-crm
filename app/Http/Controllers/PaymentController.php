<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Prospek;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Display invoices list for CS & Admin.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isCsOrAdmin = in_array(strtolower($user->role), ['cs', 'admin']);

        $query = Invoice::with(['creator', 'user', 'latestPayment']);

        if (!$isCsOrAdmin) {
            // Customer / standard user only sees their own invoices
            $query->where('user_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('customer_phone', 'like', "%{$s}%");
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('due_date', Carbon::parse($request->date));
        }

        $invoices = $query->orderByDesc('id')->paginate(10)->withQueryString();

        return view('payments.index', compact('invoices', 'isCsOrAdmin'));
    }

    /**
     * Show form to create invoice (CS & Admin).
     */
    public function create()
    {
        Gate::authorize('create', Invoice::class);

        $prospeks = Prospek::whereIn('status', ['WARM', 'HOT', 'CLOSING', 'LUNAS'])
            ->orderBy('name')
            ->take(50)
            ->get();

        return view('payments.create', compact('prospeks'));
    }

    /**
     * Store new invoice.
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Invoice::class);

        $validated = $request->validate([
            'customer_name'  => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'amount'         => 'required|numeric|min:1000',
            'due_date'       => 'required|date|after_or_equal:today',
            'notes'          => 'nullable|string|max:1000',
            'prospek_id'     => 'nullable|exists:prospeks,id',
            'user_id'        => 'nullable|exists:users,id',
        ], [
            'customer_name.required' => 'Nama pelanggan / prospek wajib diisi.',
            'amount.required'        => 'Nominal tagihan wajib diisi.',
            'amount.min'             => 'Nominal tagihan minimal Rp 1.000.',
            'due_date.required'      => 'Tanggal jatuh tempo wajib diisi.',
            'due_date.after_or_equal'=> 'Tanggal jatuh tempo minimal hari ini.',
        ]);

        $invoice = $this->paymentService->createInvoice($validated, auth()->user());

        return redirect()->route('payments.show', $invoice->id)
            ->with('success', "Tagihan {$invoice->invoice_number} berhasil dibuat.");
    }

    /**
     * Display invoice detail & payment history.
     */
    public function show(Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        $invoice->load(['payments' => function ($q) {
            $q->orderByDesc('id');
        }, 'creator', 'user', 'prospek']);

        // Auto-sync expiration for pending payments
        foreach ($invoice->payments as $p) {
            $p->isExpired();
        }

        return view('payments.show', compact('invoice'));
    }

    /**
     * Initiate a new payment transaction attempt.
     */
    public function createPayment(Request $request, Invoice $invoice)
    {
        Gate::authorize('view', $invoice);

        $validated = $request->validate([
            'payment_method' => 'required|in:qris,bank_transfer,e_wallet',
        ]);

        $payment = $this->paymentService->initiatePayment($invoice, $validated['payment_method']);

        return redirect()->route('payments.simulate', $payment->transaction_id)
            ->with('success', 'Transaksi pembayaran berhasil dibuat.');
    }

    /**
     * Simulation page (QRIS 5-minute countdown, bank transfer, e-wallet).
     */
    public function simulate(string $transactionId)
    {
        $payment = $this->paymentService->getPaymentWithStatusSync($transactionId);
        Gate::authorize('simulate', $payment);

        $invoice = $payment->invoice;
        $remainingSeconds = $payment->remaining_seconds;

        return view('payments.simulate', compact('payment', 'invoice', 'remainingSeconds'));
    }

    /**
     * Process simulated payment trigger (success, failed, expired).
     */
    public function processSimulation(Request $request, string $transactionId)
    {
        $payment = Payment::where('transaction_id', $transactionId)->firstOrFail();
        Gate::authorize('simulate', $payment);

        $request->validate([
            'action' => 'required|in:success,failed,expired',
        ]);

        $updatedPayment = $this->paymentService->simulatePaymentAction($transactionId, $request->action);

        $msg = match ($updatedPayment->status) {
            'paid'    => 'Simulasi pembayaran BERHASIL. Status telah diperbarui menjadi PAID.',
            'failed'  => 'Simulasi pembayaran GAGAL. Status telah diperbarui menjadi FAILED.',
            'expired' => 'Simulasi kedaluwarsa. Status telah diperbarui menjadi EXPIRED.',
            default   => 'Status pembayaran telah diperbarui.',
        };

        return redirect()->route('payments.simulate', $transactionId)->with('success', $msg);
    }

    /**
     * Transaction history for CS and Admin.
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $isCsOrAdmin = in_array(strtolower($user->role), ['cs', 'admin']);

        $query = Payment::with(['invoice.creator', 'invoice.user']);

        if (!$isCsOrAdmin) {
            $query->whereHas('invoice', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('transaction_id', 'like', "%{$s}%")
                  ->orWhereHas('invoice', function ($iq) use ($s) {
                      $iq->where('invoice_number', 'like', "%{$s}%")
                         ->orWhere('customer_name', 'like', "%{$s}%");
                  });
            });
        }

        $payments = $query->orderByDesc('id')->paginate(10)->withQueryString();

        return view('payments.history', compact('payments', 'isCsOrAdmin'));
    }
}
