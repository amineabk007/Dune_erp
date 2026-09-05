<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreZoneRequest;
use App\Http\Requests\UpdateZoneRequest;
use App\Models\Zone;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class ZoneController extends Controller implements HasMiddleware
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
        $zones = Zone::withCount('tables')->orderBy('name')->paginate(20);

        return view('zones.index', compact('zones'));
    }

    public function create(): View
    {
        return view('zones.create');
    }

    public function store(StoreZoneRequest $request): RedirectResponse
    {
        $zone = Zone::create($request->validated());

        $this->audit->log('create', 'zones', $zone, null, $zone->only(['name', 'description', 'is_active']));

        return redirect()->route('zones.index')->with('status', 'Zone créée.');
    }

    public function edit(Zone $zone): View
    {
        return view('zones.edit', compact('zone'));
    }

    public function update(UpdateZoneRequest $request, Zone $zone): RedirectResponse
    {
        $old = $zone->only(['name', 'description', 'is_active']);
        $zone->update($request->validated());

        $this->audit->log('update', 'zones', $zone, $old, $zone->only(['name', 'description', 'is_active']));

        return redirect()->route('zones.index')->with('status', 'Zone mise à jour.');
    }

    public function destroy(Zone $zone): RedirectResponse
    {
        if ($zone->tables()->exists()) {
            return back()->withErrors(['zone' => 'Impossible de supprimer une zone qui contient des tables.']);
        }

        $this->audit->log('delete', 'zones', $zone, $zone->only(['name', 'description', 'is_active']), null);
        $zone->delete();

        return redirect()->route('zones.index')->with('status', 'Zone supprimée.');
    }
}
