<?php

use App\Enums\DayType;
use App\Models\Branch;
use App\Models\Field;
use App\Models\PricingRule;
use App\Models\User;

/**
 * CRUD aturan harga. Izin mengikuti lapangan — lihat PricingRulePolicy
 * dan docs/03-user-stories.md US-09.
 */
function dataRule(array $ubah = []): array
{
    return array_merge([
        'day_type' => 'weekday',
        'start_time' => '08:00',
        'end_time' => '17:00',
        'price' => 150000,
        'member_price' => null,
    ], $ubah);
}

test('tamu diarahkan ke halaman login', function () {
    $field = Field::factory()->create();

    $this->get(route('fields.pricing.index', $field))->assertRedirect(route('login'));
});

test('owner bisa membuka pengaturan harga', function () {
    $owner = User::factory()->owner()->create();
    $field = Field::factory()->create();

    $this->actingAs($owner)->get(route('fields.pricing.index', $field))->assertOk();
});

test('admin bisa membuka harga lapangan cabangnya', function () {
    $cabang = Branch::factory()->create();
    $admin = User::factory()->admin($cabang)->create();
    $field = Field::factory()->forBranch($cabang)->create();

    $this->actingAs($admin)->get(route('fields.pricing.index', $field))->assertOk();
});

test('admin ditolak membuka harga lapangan cabang lain', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();
    $admin = User::factory()->admin($cabangA)->create();
    $fieldB = Field::factory()->forBranch($cabangB)->create();

    $this->actingAs($admin)->get(route('fields.pricing.index', $fieldB))->assertForbidden();
});

test('kasir ditolak mengakses harga', function () {
    $cabang = Branch::factory()->create();
    $kasir = User::factory()->kasir($cabang)->create();
    $field = Field::factory()->forBranch($cabang)->create();

    $this->actingAs($kasir)->get(route('fields.pricing.index', $field))->assertForbidden();
});

test('admin ditolak menambah harga di lapangan cabang lain', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();
    $admin = User::factory()->admin($cabangA)->create();
    $fieldB = Field::factory()->forBranch($cabangB)->create();

    $this->actingAs($admin)
        ->post(route('fields.pricing.store', $fieldB), dataRule())
        ->assertForbidden();

    expect(PricingRule::count())->toBe(0);
});

test('owner bisa menambah aturan harga', function () {
    $owner = User::factory()->owner()->create();
    $field = Field::factory()->create();

    $this->actingAs($owner)
        ->post(route('fields.pricing.store', $field), dataRule())
        ->assertRedirect(route('fields.pricing.index', $field));

    $rule = PricingRule::first();

    expect($rule->field_id)->toBe($field->id)
        ->and($rule->day_type)->toBe(DayType::Weekday)
        ->and($rule->price)->toBe(150000);
});

test('jam selesai harus setelah jam mulai', function () {
    $owner = User::factory()->owner()->create();
    $field = Field::factory()->create();

    $this->actingAs($owner)->post(route('fields.pricing.store', $field), dataRule([
        'start_time' => '17:00',
        'end_time' => '08:00',
    ]))->assertSessionHasErrors('end_time');
});

/**
 * Overlap = harga ambigu. Pelanggan bisa ditagih berbeda tergantung urutan baris.
 */
test('rentang jam yang tumpang tindih ditolak', function () {
    $owner = User::factory()->owner()->create();
    $field = Field::factory()->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->create();

    // 12:00–20:00 menabrak 08:00–17:00.
    $this->actingAs($owner)->post(route('fields.pricing.store', $field), dataRule([
        'start_time' => '12:00',
        'end_time' => '20:00',
    ]))->assertSessionHasErrors('start_time');

    expect(PricingRule::count())->toBe(1);
});

test('rentang bersebelahan tanpa tumpang tindih diterima', function () {
    $owner = User::factory()->owner()->create();
    $field = Field::factory()->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->create();

    // 17:00 eksklusif di rule pertama, jadi tidak dianggap bentrok.
    $this->actingAs($owner)->post(route('fields.pricing.store', $field), dataRule([
        'start_time' => '17:00',
        'end_time' => '23:00',
    ]))->assertRedirect(route('fields.pricing.index', $field));

    expect(PricingRule::count())->toBe(2);
});

test('tipe hari berbeda boleh punya rentang jam sama', function () {
    $owner = User::factory()->owner()->create();
    $field = Field::factory()->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->create();

    $this->actingAs($owner)->post(route('fields.pricing.store', $field), dataRule([
        'day_type' => 'weekend',
    ]))->assertRedirect(route('fields.pricing.index', $field));

    expect(PricingRule::count())->toBe(2);
});

test('lapangan berbeda boleh punya rentang jam sama', function () {
    $owner = User::factory()->owner()->create();
    $fieldA = Field::factory()->create();
    $fieldB = Field::factory()->create();

    PricingRule::factory()->forField($fieldA)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->create();

    $this->actingAs($owner)
        ->post(route('fields.pricing.store', $fieldB), dataRule())
        ->assertRedirect(route('fields.pricing.index', $fieldB));

    expect(PricingRule::count())->toBe(2);
});

test('rule tidak dianggap bentrok dengan dirinya sendiri saat diedit', function () {
    $owner = User::factory()->owner()->create();
    $field = Field::factory()->create();

    $rule = PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->create();

    $this->actingAs($owner)->put(route('pricing.update', $rule), dataRule([
        'price' => 200000,
    ]))->assertRedirect(route('fields.pricing.index', $field));

    expect($rule->fresh()->price)->toBe(200000);
});

test('edit tetap ditolak bila menabrak rule lain', function () {
    $owner = User::factory()->owner()->create();
    $field = Field::factory()->create();

    $rule = PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '17:00:00')
        ->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('17:00:00', '23:00:00')
        ->create();

    $this->actingAs($owner)->put(route('pricing.update', $rule), dataRule([
        'end_time' => '20:00',
    ]))->assertSessionHasErrors('start_time');
});

test('admin ditolak mengedit harga lapangan cabang lain', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();
    $admin = User::factory()->admin($cabangA)->create();
    $fieldB = Field::factory()->forBranch($cabangB)->create();
    $rule = PricingRule::factory()->forField($fieldB)->price(150000)->create();

    $this->actingAs($admin)
        ->put(route('pricing.update', $rule), dataRule(['price' => 1]))
        ->assertForbidden();

    expect($rule->fresh()->price)->toBe(150000);
});

test('admin ditolak menghapus harga lapangan cabang lain', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();
    $admin = User::factory()->admin($cabangA)->create();
    $fieldB = Field::factory()->forBranch($cabangB)->create();
    $rule = PricingRule::factory()->forField($fieldB)->create();

    $this->actingAs($admin)
        ->delete(route('pricing.destroy', $rule))
        ->assertForbidden();

    expect(PricingRule::find($rule->id))->not->toBeNull();
});

test('owner bisa menghapus aturan harga', function () {
    $owner = User::factory()->owner()->create();
    $field = Field::factory()->create();
    $rule = PricingRule::factory()->forField($field)->create();

    $this->actingAs($owner)
        ->delete(route('pricing.destroy', $rule))
        ->assertRedirect(route('fields.pricing.index', $field));

    expect(PricingRule::find($rule->id))->toBeNull()
        ->and(PricingRule::withTrashed()->find($rule->id))->not->toBeNull();
});

/**
 * US-09: jam operasional yang belum ter-cover ditandai di preview.
 */
test('preview menandai jam tanpa harga sebagai gap', function () {
    $owner = User::factory()->owner()->create();
    $cabang = Branch::factory()->create([
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '12:00'],
            'weekend' => ['open' => '08:00', 'close' => '12:00'],
        ],
    ]);
    $field = Field::factory()->forBranch($cabang)->create();

    // Hanya menutup 08:00–10:00; 10:00 & 11:00 jadi gap di 7 hari = 14 gap.
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '10:00:00')
        ->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekend)
        ->between('08:00:00', '10:00:00')
        ->create();

    $this->actingAs($owner)
        ->get(route('fields.pricing.index', $field))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('matrix.gaps', 14)
            ->where('matrix.hours', ['08:00', '09:00', '10:00', '11:00'])
        );
});

test('preview tanpa gap saat seluruh jam operasional tertutup', function () {
    $owner = User::factory()->owner()->create();
    $cabang = Branch::factory()->create([
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '12:00'],
            'weekend' => ['open' => '08:00', 'close' => '12:00'],
        ],
    ]);
    $field = Field::factory()->forBranch($cabang)->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '12:00:00')
        ->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekend)
        ->between('08:00:00', '12:00:00')
        ->create();

    $this->actingAs($owner)
        ->get(route('fields.pricing.index', $field))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('matrix.gaps', 0));
});

test('preview memakai hasil resolusi, hari spesifik menang', function () {
    $owner = User::factory()->owner()->create();
    $cabang = Branch::factory()->create([
        'operating_hours' => [
            'weekday' => ['open' => '08:00', 'close' => '09:00'],
            'weekend' => ['open' => '08:00', 'close' => '09:00'],
        ],
    ]);
    $field = Field::factory()->forBranch($cabang)->create();

    PricingRule::factory()->forField($field)
        ->dayType(DayType::Weekday)
        ->between('08:00:00', '09:00:00')
        ->price(150000)
        ->create();
    PricingRule::factory()->forField($field)
        ->dayType(DayType::Wednesday)
        ->between('08:00:00', '09:00:00')
        ->price(99000)
        ->create();

    $this->actingAs($owner)
        ->get(route('fields.pricing.index', $field))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('matrix.cells.wednesday.08:00', 99000)
            ->where('matrix.cells.monday.08:00', 150000)
        );
});
