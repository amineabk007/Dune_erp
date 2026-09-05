<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRestaurantTableRequest;
use App\Http\Requests\UpdateRestaurantTableRequest;
use App\Models\RestaurantTable;
use App\Models\Zone;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class RestaurantTableController extends Controller implements HasMiddleware
{
    public function __construct(private readonly AuditService $audit) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:tables.manage'),
        ];
    }

    public function index(): View
    {
        $tables = RestaurantTable::with('zone')->orderBy('zone_id')->orderBy('name')->paginate(30);

        return view('tables.index', compact('tables'));
    }

    public function create(): View
    {
        return view('tables.create', ['zones' => Zone::where('is_active', true)->orderBy('name')->get()]);
    }

    public function store(StoreRestaurantTableRequest $request): RedirectResponse
    {
        $table = RestaurantTable::create($request->validated());

        $this->audit->log('create', 'tables', $table, null, $table->only(['zone_id', 'name', 'capacity', 'status']));

        return redirect()->route('tables.index')->with('status', 'Table créée.');
    }

    public function edit(RestaurantTable $table): View
    {
        return view('tables.edit', [
            'table' => $table,
            'zones' => Zone::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateRestaurantTableRequest $request, RestaurantTable $table): RedirectResponse
    {
        $old = $table->only(['zone_id', 'name', 'capacity', 'status']);
        $table->update($request->validated());

        $this->audit->log('update', 'tables', $table, $old, $table->only(['zone_id', 'name', 'capacity', 'status']));

        return redirect()->route('tables.index')->with('status', 'Table mise à jour.');
    }

    public function destroy(RestaurantTable $table): RedirectResponse
    {
        $this->audit->log('delete', 'tables', $table, $table->only(['zone_id', 'name', 'capacity', 'status']), null);
        $table->delete();

        return redirect()->route('tables.index')->with('status', 'Table supprimée.');
    }
}
