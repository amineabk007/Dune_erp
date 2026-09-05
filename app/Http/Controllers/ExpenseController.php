<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\Supplier;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ExpenseController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ExpenseService $expenses) {}

    public static function middleware(): array
    {
        return [new Middleware('permission:expenses.manage')];
    }

    public function index(): View
    {
        $expenses = Expense::with('supplier')->latest('expense_date')->paginate(20);

        return view('expenses.index', compact('expenses'));
    }

    public function create(): View
    {
        $suppliers = Supplier::orderBy('name')->get();

        return view('expenses.create', compact('suppliers'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $this->expenses->create($request->user(), $request->validated());

        return redirect()->route('expenses.index')->with('status', 'Dépense enregistrée.');
    }

    public function edit(Expense $expense): View
    {
        $suppliers = Supplier::orderBy('name')->get();

        return view('expenses.edit', compact('expense', 'suppliers'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->expenses->update($expense, $request->validated());

        return redirect()->route('expenses.index')->with('status', 'Dépense mise à jour.');
    }
}
