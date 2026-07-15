<?php

namespace App\Http\Controllers;

use App\Enums\DayType;
use App\Http\Requests\StorePricingRuleRequest;
use App\Http\Requests\UpdatePricingRuleRequest;
use App\Models\Field;
use App\Models\PricingRule;
use App\Services\PricingMatrixService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Pengaturan harga per lapangan. Lihat docs/01-prd.md Modul 4.
 */
class PricingRuleController extends Controller
{
    public function __construct(private readonly PricingMatrixService $matrix) {}

    public function index(Field $field): Response
    {
        Gate::authorize('viewAny', [PricingRule::class, $field]);

        $field->load(['branch:id,name,operating_hours', 'pricingRules']);

        return Inertia::render('pricing/index', [
            'field' => [
                'id' => $field->id,
                'name' => $field->name,
                'branch_name' => $field->branch->name,
            ],
            'rules' => $field->pricingRules
                ->sortBy([['day_type', 'asc'], ['start_time', 'asc']])
                ->values()
                ->map(fn (PricingRule $rule): array => [
                    'id' => $rule->id,
                    'day_type' => $rule->day_type->value,
                    'day_label' => $rule->day_type->label(),
                    'start_time' => substr($rule->start_time, 0, 5),
                    'end_time' => substr($rule->end_time, 0, 5),
                    'price' => $rule->price,
                    'member_price' => $rule->member_price,
                ])
                ->all(),
            'matrix' => $this->matrix->build($field),
            'dayTypes' => DayType::options(),
        ]);
    }

    public function store(StorePricingRuleRequest $request, Field $field): RedirectResponse
    {
        $field->pricingRules()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aturan harga berhasil ditambahkan.']);

        return to_route('fields.pricing.index', $field);
    }

    public function update(UpdatePricingRuleRequest $request, PricingRule $pricingRule): RedirectResponse
    {
        $pricingRule->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aturan harga berhasil diperbarui.']);

        return to_route('fields.pricing.index', $pricingRule->field_id);
    }

    public function destroy(PricingRule $pricingRule): RedirectResponse
    {
        Gate::authorize('delete', $pricingRule);

        $fieldId = $pricingRule->field_id;
        $pricingRule->delete();

        // Menghapus rule bisa menciptakan gap; preview matriks akan menandainya merah.
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Aturan harga berhasil dihapus.']);

        return to_route('fields.pricing.index', $fieldId);
    }
}
