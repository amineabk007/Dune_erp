<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EmployeeController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AuditService $audit) {}

    public static function middleware(): array
    {
        return [new Middleware('permission:employees.manage')];
    }

    public function index(): View
    {
        $employees = Employee::with('user')->orderBy('name')->paginate(20);

        return view('employees.index', compact('employees'));
    }

    public function create(): View
    {
        $users = User::orderBy('name')->get();

        return view('employees.create', compact('users'));
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create([...$request->validated(), 'created_by' => $request->user()->id]);

        $this->audit->log('create', 'employees', $employee, null, $employee->only(['name', 'position']));

        return redirect()->route('employees.index')->with('status', 'Employé créé.');
    }

    public function edit(Employee $employee): View
    {
        $users = User::orderBy('name')->get();

        return view('employees.edit', compact('employee', 'users'));
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $old = $employee->only(['name', 'position', 'phone', 'email', 'salary', 'user_id']);
        $employee->update($request->validated());

        $this->audit->log('update', 'employees', $employee, $old, $employee->only([
            'name', 'position', 'phone', 'email', 'salary', 'user_id',
        ]));

        return redirect()->route('employees.index')->with('status', 'Employé mis à jour.');
    }

    public function toggleActive(Employee $employee): RedirectResponse
    {
        $old = ['is_active' => $employee->is_active];
        $employee->is_active = ! $employee->is_active;
        $employee->save();

        $this->audit->log(
            $employee->is_active ? 'reactivate' : 'deactivate',
            'employees',
            $employee,
            $old,
            ['is_active' => $employee->is_active]
        );

        return back()->with('status', $employee->is_active ? 'Employé réactivé.' : 'Employé désactivé.');
    }
}
