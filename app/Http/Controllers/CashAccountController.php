<?php
namespace App\Http\Controllers;

use App\Models\CashAccount;
use Illuminate\Http\Request;

class CashAccountController extends Controller
{
    public function index()
    {
        $accounts = CashAccount::latest()->get();
        $total    = $accounts->sum('balance');
        return view('cash-accounts.index', compact('accounts', 'total'));
    }

    public function create()
    {
        return view('cash-accounts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:150|unique:cash_accounts,name',
            'balance' => 'required|numeric|min:0',
            'notes'   => 'nullable|string|max:500',
        ]);

        CashAccount::create($request->only('name', 'balance', 'notes'));

        return redirect()->route('cash-accounts.index')
            ->with('success', 'Cash account created.');
    }

    public function edit(CashAccount $cashAccount)
    {
        return view('cash-accounts.edit', compact('cashAccount'));
    }

    public function update(Request $request, CashAccount $cashAccount)
    {
        $request->validate([
            'name'    => 'required|string|max:150|unique:cash_accounts,name,' . $cashAccount->id,
            'balance' => 'required|numeric|min:0',
            'notes'   => 'nullable|string|max:500',
        ]);

        $cashAccount->update($request->only('name', 'balance', 'notes'));

        return redirect()->route('cash-accounts.index')
            ->with('success', 'Cash account updated.');
    }

    public function destroy(CashAccount $cashAccount)
    {
        $cashAccount->delete();
        return redirect()->route('cash-accounts.index')
            ->with('success', 'Cash account deleted.');
    }
}