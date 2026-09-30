<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminBankAccountController extends Controller
{
    public function index(): View
    {
        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        return view('admin.bank_account.index', compact('bankAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:200',
            'notes'          => 'nullable|string|max:500',
        ]);

        BankAccount::create($request->only(['bank_name', 'account_number', 'account_name', 'notes']));

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Rekening bank berhasil ditambahkan.');
    }

    public function update(Request $request, BankAccount $bankAccount): RedirectResponse
    {
        $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:200',
            'notes'          => 'nullable|string|max:500',
        ]);

        $bankAccount->update($request->only(['bank_name', 'account_number', 'account_name', 'notes']));

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Rekening bank berhasil diperbarui.');
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();
        return redirect()->route('admin.bank-accounts.index')->with('success', 'Rekening bank berhasil dihapus.');
    }

    public function toggleStatus(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update(['is_active' => !$bankAccount->is_active]);
        $status = $bankAccount->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('admin.bank-accounts.index')->with('success', "Rekening bank berhasil {$status}.");
    }
}
