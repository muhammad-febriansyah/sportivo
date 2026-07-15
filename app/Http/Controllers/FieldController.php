<?php

namespace App\Http\Controllers;

use App\Enums\FieldStatus;
use App\Enums\SurfaceType;
use App\Http\Requests\StoreFieldRequest;
use App\Http\Requests\UpdateFieldRequest;
use App\Models\Branch;
use App\Models\Field;
use App\Models\User;
use App\Services\FieldService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class FieldController extends Controller
{
    public function __construct(private readonly FieldService $fields) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Field::class);

        return Inertia::render('fields/index', [
            'fields' => $this->fields->paginate($request->user(), [
                'search' => $request->string('search')->value() ?: null,
                'branch_id' => $request->integer('branch_id') ?: null,
                'status' => $request->string('status')->value() ?: null,
                'sort' => $request->string('sort')->value() ?: null,
                'direction' => $request->string('direction')->value() ?: null,
            ]),
            'query' => $request->only(['search', 'branch_id', 'status', 'sort', 'direction']),
            'branches' => $this->branchOptions($request->user()),
            'statuses' => FieldStatus::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Field::class);

        return Inertia::render('fields/create', [
            'branches' => $this->branchOptions($request->user()),
            'surfaceTypes' => SurfaceType::options(),
            'statuses' => FieldStatus::options(),
            'lockedBranchId' => $request->user()->isOwner() ? null : $request->user()->branch_id,
        ]);
    }

    public function store(StoreFieldRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data): void {
            $data['photo_path'] = $this->storePhoto($request);

            unset($data['photo']);

            Field::create($data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lapangan berhasil ditambahkan.']);

        return to_route('fields.index');
    }

    public function edit(Request $request, Field $field): Response
    {
        Gate::authorize('update', $field);

        return Inertia::render('fields/edit', [
            'field' => [
                'id' => $field->id,
                'branch_id' => $field->branch_id,
                'name' => $field->name,
                'surface_type' => $field->surface_type->value,
                'size' => $field->size,
                'description' => $field->description,
                'status' => $field->status->value,
                'photo_url' => $field->photo_path ? Storage::url($field->photo_path) : null,
            ],
            'branches' => $this->branchOptions($request->user()),
            'surfaceTypes' => SurfaceType::options(),
            'statuses' => FieldStatus::options(),
            'lockedBranchId' => $request->user()->isOwner() ? null : $request->user()->branch_id,
        ]);
    }

    public function update(UpdateFieldRequest $request, Field $field): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $field): void {
            $fotoBaru = $this->storePhoto($request);

            unset($data['photo']);

            if ($fotoBaru !== null) {
                if ($field->photo_path) {
                    Storage::disk('public')->delete($field->photo_path);
                }

                $data['photo_path'] = $fotoBaru;
            }

            $field->update($data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lapangan berhasil diperbarui.']);

        return to_route('fields.index');
    }

    public function destroy(Field $field): RedirectResponse
    {
        Gate::authorize('delete', $field);

        // CATATAN: docs/01-prd.md Modul 3 mensyaratkan lapangan dengan booking
        // aktif tidak boleh dihapus. Tabel `bookings` baru dibuat di Modul 6,
        // jadi pemeriksaan itu WAJIB ditambahkan di sini saat modul tersebut
        // dikerjakan. Untuk sekarang soft delete menjaga data historis tetap ada.
        $field->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Lapangan berhasil dihapus.']);

        return to_route('fields.index');
    }

    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('fields', 'public');
    }

    /**
     * Admin hanya melihat cabangnya sendiri sebagai pilihan.
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function branchOptions(User $user): array
    {
        return Branch::query()
            ->select(['id', 'name'])
            ->when(! $user->isOwner(), fn ($query) => $query->whereKey($user->branch_id))
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch): array => ['value' => $branch->id, 'label' => $branch->name])
            ->all();
    }
}
