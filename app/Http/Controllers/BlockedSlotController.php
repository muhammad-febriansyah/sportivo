<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlockedSlotRequest;
use App\Models\BlockedSlot;
use App\Models\Branch;
use App\Models\Field;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Blocking slot — menutup lapangan untuk maintenance atau event privat.
 * Lihat docs/01-prd.md Modul 12 dan docs/03-user-stories.md US-10.
 */
class BlockedSlotController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', BlockedSlot::class);

        $blocks = BlockedSlot::query()
            ->visibleTo($request->user())
            ->with(['branch:id,name', 'field:id,name', 'createdBy:id,name'])
            // Blokir yang sudah lewat tidak lagi relevan untuk dikelola.
            ->whereDate('block_date', '>=', Carbon::today())
            ->orderBy('block_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('blocked-slots/index', [
            'blocks' => $blocks->through(fn (BlockedSlot $b): array => [
                'id' => $b->id,
                'branch_name' => $b->branch->name,
                // Null berarti seluruh lapangan cabang ikut tertutup.
                'field_name' => $b->field?->name,
                'block_date' => $b->block_date->toDateString(),
                'start_time' => substr($b->start_time, 0, 5),
                'end_time' => substr($b->end_time, 0, 5),
                'reason' => $b->reason,
                'created_by_name' => $b->createdBy?->name,
            ]),
            'query' => $request->only(['search', 'sort', 'direction']),
            'branches' => $this->branchOptions($request->user()),
            'fields' => $this->fieldOptions($request->user()),
        ]);
    }

    public function store(StoreBlockedSlotRequest $request): RedirectResponse
    {
        BlockedSlot::create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Slot berhasil diblokir.']);

        return to_route('blocked-slots.index');
    }

    public function destroy(BlockedSlot $blockedSlot): RedirectResponse
    {
        Gate::authorize('delete', $blockedSlot);

        $blockedSlot->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Blokir berhasil dihapus.']);

        return to_route('blocked-slots.index');
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

    /**
     * @return array<int, array{value: int, label: string, branch_id: int}>
     */
    private function fieldOptions(User $user): array
    {
        return Field::query()
            ->visibleTo($user)
            ->select(['id', 'name', 'branch_id'])
            ->orderBy('name')
            ->get()
            ->map(fn (Field $f): array => [
                'value' => $f->id,
                'label' => $f->name,
                'branch_id' => $f->branch_id,
            ])
            ->all();
    }
}
