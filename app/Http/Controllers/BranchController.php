<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Laravolt\Indonesia\Models\City;
use Laravolt\Indonesia\Models\District;
use Laravolt\Indonesia\Models\Province;

class BranchController extends Controller
{
    public function __construct(private readonly BranchService $branches) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Branch::class);

        return Inertia::render('branches/index', [
            'branches' => $this->branches->paginate([
                'search' => $request->string('search')->value() ?: null,
                'sort' => $request->string('sort')->value() ?: null,
                'direction' => $request->string('direction')->value() ?: null,
            ]),
            'query' => $request->only(['search', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Branch::class);

        return Inertia::render('branches/create', [
            'provinces' => $this->provinceOptions(),
        ]);
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data): void {
            $data['photo_path'] = $this->storePhoto($request);

            unset($data['photo']);

            // Observer BranchObserver otomatis membuat baris branch_settings.
            Branch::create($data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cabang berhasil ditambahkan.']);

        return to_route('branches.index');
    }

    public function edit(Branch $branch): Response
    {
        Gate::authorize('update', $branch);

        return Inertia::render('branches/edit', [
            'branch' => [
                'id' => $branch->id,
                'name' => $branch->name,
                'code' => $branch->code,
                'address' => $branch->address,
                'province_id' => $branch->province_id,
                'city_id' => $branch->city_id,
                'district_id' => $branch->district_id,
                'phone' => $branch->phone,
                'operating_hours' => $branch->operating_hours,
                'photo_url' => $branch->photo_path ? Storage::url($branch->photo_path) : null,
                'is_active' => $branch->is_active,
            ],
            'provinces' => $this->provinceOptions(),
            // Dropdown bertingkat butuh isi awal agar pilihan tersimpan langsung terlihat.
            'cities' => $this->cityOptions($branch->province_id),
            'districts' => $this->districtOptions($branch->city_id),
        ]);
    }

    public function update(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $data, $branch): void {
            $fotoBaru = $this->storePhoto($request);

            unset($data['photo']);

            if ($fotoBaru !== null) {
                // Buang file lama agar disk tidak menumpuk foto yatim.
                if ($branch->photo_path) {
                    Storage::disk('public')->delete($branch->photo_path);
                }

                $data['photo_path'] = $fotoBaru;
            }

            $branch->update($data);
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cabang berhasil diperbarui.']);

        return to_route('branches.index');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        Gate::authorize('delete', $branch);

        $blockers = $this->branches->deletionBlockers($branch);

        if ($blockers !== []) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $blockers[0]]);

            return back();
        }

        $branch->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cabang berhasil dihapus.']);

        return to_route('branches.index');
    }

    private function storePhoto(Request $request): ?string
    {
        if (! $request->hasFile('photo')) {
            return null;
        }

        return $request->file('photo')->store('branches', 'public');
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function provinceOptions(): array
    {
        return Province::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get()
            ->map(fn (Province $province): array => ['value' => (int) $province->id, 'label' => $province->name])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function cityOptions(?int $provinceId): array
    {
        if ($provinceId === null) {
            return [];
        }

        $province = Province::select(['id', 'code'])->find($provinceId);

        if ($province === null) {
            return [];
        }

        return City::query()
            ->select(['id', 'name'])
            ->where('province_code', $province->code)
            ->orderBy('name')
            ->get()
            ->map(fn (City $city): array => ['value' => (int) $city->id, 'label' => $city->name])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function districtOptions(?int $cityId): array
    {
        if ($cityId === null) {
            return [];
        }

        $city = City::select(['id', 'code'])->find($cityId);

        if ($city === null) {
            return [];
        }

        return District::query()
            ->select(['id', 'name'])
            ->where('city_code', $city->code)
            ->orderBy('name')
            ->get()
            ->map(fn (District $district): array => ['value' => (int) $district->id, 'label' => $district->name])
            ->all();
    }
}
