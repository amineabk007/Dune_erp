<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Foundation-phase landing page. The full KPI dashboard (CA, tickets,
     * occupancy, stock alerts...) is built in the Reporting phase once the
     * modules feeding it (orders, payments, stock) exist.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.index', [
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')->sort()->values(),
        ]);
    }
}
