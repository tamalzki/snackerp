<?php
namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\Deposit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepositController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $deposits = Deposit::with('creator')
            ->when($search, function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('deposits.index', compact('deposits', 'search'));
    }

    public function create()
    {
        $cashAccounts = CashAccount::orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        return view('deposits.create', compact('cashAccounts', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'source_type'  => 'required|in:cash,bank',
            'source_id'    => 'required|integer',
            'amount'       => 'required|numeric|min:0.01',
            'deposit_date' => 'required|date',
            'reference'    => 'nullable|string|max:100',
            'notes'        => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request) {
            Deposit::create([
                'source_type'  => $request->source_type,
                'source_id'    => $request->source_id,
                'amount'       => $request->amount,
                'deposit_date' => $request->deposit_date,
                'reference'    => $request->reference,
                'notes'        => $request->notes,
                'created_by'   => auth()->id(),
            ]);

            // Add to account balance
            if ($request->source_type === 'cash') {
                CashAccount::where('id', $request->source_id)
                    ->increment('balance', $request->amount);
            } else {
                BankAccount::where('id', $request->source_id)
                    ->increment('balance', $request->amount);
            }
        });

        return redirect()->route('deposits.index')
            ->with('success', 'Deposit recorded and balance updated.');
    }

    public function show(Deposit $deposit)
    {
        return view('deposits.show', compact('deposit'));
    }
}