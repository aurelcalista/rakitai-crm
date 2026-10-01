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
        $pageTitle = 'Rekening Bank';
        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        return view('admin.bank_account.index', compact('pageTitle', 'bankAccounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:200',
            'notes'          => 'nullable|string|max:500',
            'is_active'      => 'nullable',
        ]);

        $isActive = true;
        if ($request->has('is_active')) {
            $isActive = in_array($request->is_active, [1, '1', true, 'true', 'Aktif', 'aktif', 'on'], true);
        }

        BankAccount::create([
            'bank_name'      => trim($validated['bank_name']),
            'account_number' => trim($validated['account_number']),
            'account_name'   => trim($validated['account_name']),
            'notes'          => !empty($validated['notes']) ? trim($validated['notes']) : null,
            'is_active'      => $isActive,
        ]);

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Rekening bank ' . $validated['bank_name'] . ' berhasil ditambahkan.');
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $validated = $request->validate([
            'bank_name'      => 'required|string|max:100',
            'account_number' => 'required|string|max:50',
            'account_name'   => 'required|string|max:200',
            'notes'          => 'nullable|string|max:500',
            'is_active'      => 'nullable',
        ]);

        $bankAccount = BankAccount::findOrFail($id);

        $updateData = [
            'bank_name'      => trim($validated['bank_name']),
            'account_number' => trim($validated['account_number']),
            'account_name'   => trim($validated['account_name']),
            'notes'          => !empty($validated['notes']) ? trim($validated['notes']) : null,
        ];

        if ($request->has('is_active')) {
            $updateData['is_active'] = in_array($request->is_active, [1, '1', true, 'true', 'Aktif', 'aktif', 'on'], true);
        }

        $bankAccount->update($updateData);

        return redirect()->route('admin.bank-accounts.index')->with('success', 'Rekening bank ' . $bankAccount->bank_name . ' berhasil diperbarui.');
    }

    public function destroy($id): RedirectResponse
    {
        $bankAccount = BankAccount::findOrFail($id);
        $name = $bankAccount->bank_name . ' (' . $bankAccount->account_number . ')';
        $bankAccount->delete();

        return redirect()->route('admin.bank-accounts.index')->with('success', "Rekening bank {$name} berhasil dihapus.");
    }

    public function toggleStatus($id): RedirectResponse
    {
        $bankAccount = BankAccount::findOrFail($id);
        $bankAccount->update(['is_active' => !$bankAccount->is_active]);
        $status = $bankAccount->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('admin.bank-accounts.index')->with('success', "Status rekening {$bankAccount->bank_name} berhasil {$status}.");
    }
}
