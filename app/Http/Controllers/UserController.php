<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')
            ->orderBy('name')
            ->paginate(20);

        return view('users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('users.create', ['roles' => Role::orderBy('name')->get()]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->string('name'),
            'email' => $request->string('email'),
            'phone' => $request->input('phone'),
            'password' => Hash::make($request->string('password')),
            'is_active' => true,
        ]);

        $user->syncRoles($request->input('roles'));

        $this->audit->log('create', 'users', $user, null, $user->only(['name', 'email', 'phone']));

        return redirect()->route('users.index')->with('status', 'Utilisateur créé.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $old = $user->only(['name', 'email', 'phone', 'is_active']);

        $user->name = $request->string('name');
        $user->email = $request->string('email');
        $user->phone = $request->input('phone');

        if ($request->filled('password')) {
            $user->password = Hash::make($request->string('password'));
        }

        // Self-deactivation is blocked regardless of what the form submits.
        if ($request->has('is_active') && $this->requestUserIsNot($user)) {
            $user->is_active = $request->boolean('is_active');
        }

        $user->save();
        $user->syncRoles($request->input('roles'));

        $this->audit->log('update', 'users', $user, $old, $user->only(['name', 'email', 'phone', 'is_active']));

        return redirect()->route('users.index')->with('status', 'Utilisateur mis à jour.');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        // Self-lockout protection applies even to admin: it is a safety rule,
        // not a permission, so it is not subject to the admin bypass gate.
        abort_if(auth()->id() === $user->id, 403, 'Vous ne pouvez pas désactiver votre propre compte.');

        $this->authorize('deactivate', $user);

        $old = ['is_active' => $user->is_active];
        $user->is_active = ! $user->is_active;
        $user->save();

        $this->audit->log(
            $user->is_active ? 'reactivate' : 'deactivate',
            'users',
            $user,
            $old,
            ['is_active' => $user->is_active]
        );

        return back()->with('status', $user->is_active ? 'Utilisateur réactivé.' : 'Utilisateur désactivé.');
    }

    private function requestUserIsNot(User $user): bool
    {
        return auth()->id() !== $user->id;
    }
}
