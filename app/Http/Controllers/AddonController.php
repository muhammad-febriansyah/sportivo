<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddonRequest;
use App\Http\Requests\UpdateAddonRequest;
use App\Models\Addon;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Master add-on perlengkapan per cabang — docs/01-prd.md Modul 11.
 */
class AddonController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Addon::class);

        $addons = Addon::query()
            ->visibleTo($request->user())
            ->with('branch:id,name')
            ->when(
                $request->string('search')->value(),
                fn ($q, string $s) => $q->where('name', 'like', "%{$s}%")
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('addons/index', [
            'addons' => $addons->through(fn (Addon $a): array => [
                'id' => $a->id,
                'branch_id' => $a->branch_id,
                'branch_name' => $a->branch->name,
                'name' => $a->name,
                'price' => $a->price,
                'stock' => $a->stock,
                'is_active' => $a->is_active,
            ]),
            'query' => $request->only(['search', 'sort', 'direction']),
            'branches' => $this->branchOptions($request->user()),
        ]);
    }

    public function store(StoreAddonRequest $request): RedirectResponse
    {
        Addon::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Add-on berhasil ditambahkan.']);

        return to_route('addons.index');
    }

    public function update(UpdateAddonRequest $request, Addon $addon): RedirectResponse
    {
        $addon->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Add-on berhasil diperbarui.']);

        return to_route('addons.index');
    }

    public function destroy(Addon $addon): RedirectResponse
    {
        Gate::authorize('delete', $addon);

        // Soft delete: booking lama menyimpan snapshot nama & harganya sendiri,
        // jadi tagihannya tetap utuh meski master add-on dihapus.
        $addon->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Add-on berhasil dihapus.']);

        return to_route('addons.index');
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function branchOptions(User $user): array
    {
        return Branch::query()
            ->select(['id', 'name'])
            ->when(! $user->isOwner(), fn ($q) => $q->whereKey($user->branch_id))
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $b): array => ['value' => $b->id, 'label' => $b->name])
            ->all();
    }
}
