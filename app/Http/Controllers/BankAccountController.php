<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        $accounts = BankAccount::latest()->get();
        $total = $accounts->sum('balance');

        return view('bank-accounts.index', compact('accounts', 'total'));
    }

    public function create()
    {
        return view('bank-accounts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'bank_name' => 'required|string|max:150',
            'account_name' => 'required|string|max:150',
            'balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        BankAccount::create([
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => '',
            'balance' => $request->balance,
            'notes' => $request->notes,
        ]);

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account created.');
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('bank-accounts.edit', compact('bankAccount'));
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $request->validate([
            'bank_name' => 'required|string|max:150',
            'account_name' => 'required|string|max:150',
            'balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ]);

        $bankAccount->update([
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'balance' => $request->balance,
            'notes' => $request->notes,
        ]);

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account updated.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')
            ->with('success', 'Bank account deleted.');
    }
}
