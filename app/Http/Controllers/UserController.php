<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Branch;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('users/index', [
            'users' => $this->users->paginate([
                'search' => $request->string('search')->value() ?: null,
                'sort' => $request->string('sort')->value() ?: null,
                'direction' => $request->string('direction')->value() ?: null,
            ]),
            'query' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', User::class);

        return Inertia::render('users/create', [
            'branches' => $this->branchOptions(),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'password' => $data['password'],
            ]);

            $user->syncRoles([$data['role']]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User berhasil ditambahkan.']);

        return to_route('users.index');
    }

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('update', $user);

        $user->load('roles:id,name');

        return Inertia::render('users/edit', [
            'user' => [
                'id' => $user->id,
                'branch_id' => $user->branch_id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $user->roles->first()?->name,
            ],
            'branches' => $this->branchOptions(),
            'roles' => $this->roleOptions(),
            'canDeactivate' => $request->user()->can('deactivate', $user),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $isActive = $data['is_active'] ?? true;

        // Owner tidak boleh menonaktifkan dirinya sendiri — lihat UserPolicy::deactivate.
        if (! $isActive && $request->user()->cannot('deactivate', $user)) {
            return back()->withErrors([
                'is_active' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.',
            ]);
        }

        DB::transaction(function () use ($data, $user, $isActive): void {
            $attributes = [
                'branch_id' => $data['branch_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'is_active' => $isActive,
            ];

            // Kata sandi hanya diubah bila form mengisinya.
            if (filled($data['password'] ?? null)) {
                $attributes['password'] = $data['password'];
            }

            $user->update($attributes);
            $user->syncRoles([$data['role']]);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'User berhasil diperbarui.']);

        return to_route('users.index');
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function branchOptions(): array
    {
        return Branch::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch): array => [
                'value' => $branch->id,
                'label' => $branch->name,
            ])
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_map(
            fn (UserRole $role): array => ['value' => $role->value, 'label' => $role->label()],
            UserRole::cases()
        );
    }
}
