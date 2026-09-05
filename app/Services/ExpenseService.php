<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private readonly AuditService $audit) {}

    public function create(User $user, array $data): Expense
    {
        return DB::transaction(function () use ($user, $data) {
            $expense = Expense::create([...$data, 'created_by' => $user->id]);

            $this->audit->log('create', 'expenses', $expense, null, $expense->only([
                'category', 'amount', 'expense_date',
            ]));

            return $expense;
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $old = $expense->only(['category', 'description', 'amount', 'expense_date', 'paid_via']);
            $expense->update($data);

            $this->audit->log('update', 'expenses', $expense, $old, $expense->only([
                'category', 'description', 'amount', 'expense_date', 'paid_via',
            ]));

            return $expense;
        });
    }
}
