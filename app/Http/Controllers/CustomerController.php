<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Customer;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class CustomerController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AuditService $audit) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:customers.manage'),
        ];
    }

    public function index(Request $request): View
    {
        $customers = Customer::when($request->filled('q'), fn ($q) => $q->where(function ($q) use ($request) {
            $q->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('phone', 'like', '%'.$request->string('q').'%')
                ->orWhere('email', 'like', '%'.$request->string('q').'%');
        }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        $this->audit->log('create', 'customers', $customer, null, $customer->only(['name', 'phone', 'email']));

        return redirect()->route('customers.index')->with('status', 'Client créé.');
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $old = $customer->only(['name', 'phone', 'email', 'notes']);
        $customer->update($request->validated());

        $this->audit->log('update', 'customers', $customer, $old, $customer->only(['name', 'phone', 'email', 'notes']));

        return redirect()->route('customers.index')->with('status', 'Client mis à jour.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->audit->log('delete', 'customers', $customer, $customer->only(['name', 'phone', 'email']), null);
        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Client supprimé.');
    }
}
