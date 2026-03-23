<?php
namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\CashAccount;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');

        $expenses = Expense::with('creator')
            ->when($search, function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $totalExpenses = Expense::sum('amount');

        return view('expenses.index', compact('expenses', 'search', 'totalExpenses'));
    }

    public function create()
    {
        $cashAccounts = CashAccount::orderBy('name')->get();
        $bankAccounts = BankAccount::orderBy('bank_name')->get();
        return view('expenses.create', compact('cashAccounts', 'bankAccounts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:200',
            'category'     => 'required|in:utilities,salaries,rent,supplies,maintenance,transport,other',
            'amount'       => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'paid_from'    => 'required|in:cash,bank',
            'source_id'    => 'required|integer',
            'notes'        => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($request) {
            // Check sufficient balance
            if ($request->paid_from === 'cash') {
                $account = CashAccount::findOrFail($request->source_id);
                if ($account->balance < $request->amount) {
                    throw ValidationException::withMessages([
                        'amount' => "Insufficient cash balance. Available: ₱{$account->balance}"
                    ]);
                }
                $account->decrement('balance', $request->amount);
            } else {
                $account = BankAccount::findOrFail($request->source_id);
                if ($account->balance < $request->amount) {
                    throw ValidationException::withMessages([
                        'amount' => "Insufficient bank balance. Available: ₱{$account->balance}"
                    ]);
                }
                $account->decrement('balance', $request->amount);
            }

            Expense::create([
                'title'        => $request->title,
                'category'     => $request->category,
                'amount'       => $request->amount,
                'expense_date' => $request->expense_date,
                'paid_from'    => $request->paid_from,
                'source_id'    => $request->source_id,
                'notes'        => $request->notes,
                'created_by'   => auth()->id(),
            ]);
        });

        return redirect()->route('expenses.index')
            ->with('success', 'Expense recorded and balance deducted.');
    }

    public function show(Expense $expense)
    {
        return view('expenses.show', compact('expense'));
    }
}