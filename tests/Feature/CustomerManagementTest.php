<?php

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Field;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Pelanggan tidak terikat cabang — lihat CustomerPolicy dan docs/01-prd.md Modul 10.
 */
function dataCustomer(array $ubah = []): array
{
    return array_merge([
        'name' => 'Budi Santoso',
        'phone' => '081234567890',
        'email' => null,
        'is_member' => false,
        'member_until' => null,
        'notes' => null,
    ], $ubah);
}

test('tamu diarahkan ke halaman login', function () {
    $this->get(route('customers.index'))->assertRedirect(route('login'));
});

test('kasir bisa membuka daftar pelanggan', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->get(route('customers.index'))->assertOk();
});

/**
 * US-05: kasir wajib bisa membuat pelanggan saat input booking walk-in.
 */
test('kasir bisa membuat pelanggan', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)
        ->post(route('customers.store'), dataCustomer())
        ->assertRedirect(route('customers.index'));

    expect(Customer::count())->toBe(1);
});

/**
 * Nomor WA adalah identitas unik — formatnya harus tunggal.
 */
test('nomor telepon dinormalisasi ke 628xxx', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->post(route('customers.store'), dataCustomer([
        'phone' => '081234567890',
    ]));

    expect(Customer::first()->phone)->toBe('6281234567890');
});

test('format nomor berbeda dianggap pelanggan yang sama', function () {
    $kasir = User::factory()->kasir()->create();
    Customer::factory()->create(['phone' => '6281234567890']);

    // "+62 812-3456-7890" menormalisasi ke nomor yang sama.
    $this->actingAs($kasir)
        ->post(route('customers.store'), dataCustomer([
            'phone' => '+62 812-3456-7890',
        ]))
        ->assertSessionHasErrors('phone');

    expect(Customer::count())->toBe(1);
});

test('nomor diawali 8 tanpa 0 juga dinormalisasi', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->post(route('customers.store'), dataCustomer([
        'phone' => '81234567890',
    ]));

    expect(Customer::first()->phone)->toBe('6281234567890');
});

/**
 * Status member berdampak ke harga, jadi kasir tidak boleh menetapkannya.
 */
test('kasir tidak bisa menjadikan pelanggan member lewat payload', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)->post(route('customers.store'), dataCustomer([
        'is_member' => true,
        'member_until' => Carbon::today()->addYear()->toDateString(),
    ]));

    expect(Customer::first()->is_member)->toBeFalse();
});

test('admin bisa menjadikan pelanggan member', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('customers.store'), dataCustomer([
        'is_member' => true,
        'member_until' => Carbon::today()->addYear()->toDateString(),
    ]));

    expect(Customer::first()->is_member)->toBeTrue();
});

test('kasir ditolak mengedit pelanggan', function () {
    $kasir = User::factory()->kasir()->create();
    $customer = Customer::factory()->create(['name' => 'Asli']);

    $this->actingAs($kasir)
        ->put(route('customers.update', $customer), dataCustomer(['name' => 'Diubah']))
        ->assertForbidden();

    expect($customer->fresh()->name)->toBe('Asli');
});

test('admin bisa mengedit pelanggan', function () {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($admin)
        ->put(route('customers.update', $customer), dataCustomer([
            'name' => 'Nama Baru',
            'phone' => $customer->phone,
        ]))
        ->assertRedirect(route('customers.index'));

    expect($customer->fresh()->name)->toBe('Nama Baru');
});

test('tanggal berakhir dihapus saat status member dimatikan', function () {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->member()->create();

    $this->actingAs($admin)->put(route('customers.update', $customer), dataCustomer([
        'phone' => $customer->phone,
        'is_member' => false,
        'member_until' => Carbon::today()->addYear()->toDateString(),
    ]));

    expect($customer->fresh()->member_until)->toBeNull();
});

test('member kedaluwarsa dihitung bukan member aktif', function () {
    $customer = Customer::factory()->expiredMember()->create();

    expect($customer->isActiveMember())->toBeFalse()
        ->and($customer->is_member)->toBeTrue();
});

test('member tanpa tanggal berakhir berlaku selamanya', function () {
    $customer = Customer::factory()->create([
        'is_member' => true,
        'member_until' => null,
    ]);

    expect($customer->isActiveMember())->toBeTrue();
});

test('scope activeMembers mengabaikan member kedaluwarsa', function () {
    $aktif = Customer::factory()->member()->create();
    $kedaluwarsa = Customer::factory()->expiredMember()->create();
    $bukan = Customer::factory()->create();

    $hasil = Customer::query()->activeMembers()->pluck('id');

    expect($hasil)->toContain($aktif->id)
        ->not->toContain($kedaluwarsa->id)
        ->not->toContain($bukan->id);
});

/**
 * US-05: pencarian cepat by nomor WA.
 */
test('pencarian menemukan pelanggan lewat nomor tidak ternormalisasi', function () {
    $kasir = User::factory()->kasir()->create();
    Customer::factory()->create(['phone' => '6281234567890', 'name' => 'Budi']);

    $this->actingAs($kasir)
        ->getJson(route('customers.search', ['q' => '081234567890']))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Budi']);
});

test('pencarian menemukan pelanggan lewat nama', function () {
    $kasir = User::factory()->kasir()->create();
    Customer::factory()->create(['name' => 'Budi Santoso']);
    Customer::factory()->create(['name' => 'Andi']);

    $this->actingAs($kasir)
        ->getJson(route('customers.search', ['q' => 'Budi']))
        ->assertOk()
        ->assertJsonCount(1);
});

test('quick store membuat pelanggan dengan nama dan nomor saja', function () {
    $kasir = User::factory()->kasir()->create();

    $this->actingAs($kasir)
        ->postJson(route('customers.quick-store'), [
            'name' => 'Budi',
            'phone' => '081234567890',
        ])
        ->assertCreated()
        ->assertJsonFragment(['phone' => '6281234567890']);
});

/**
 * Kasir tidak perlu tahu soal duplikat — kembalikan pelanggan yang sudah ada.
 */
test('quick store mengembalikan pelanggan lama bila nomor sudah terdaftar', function () {
    $kasir = User::factory()->kasir()->create();
    $lama = Customer::factory()->create(['phone' => '6281234567890', 'name' => 'Budi Lama']);

    $this->actingAs($kasir)
        ->postJson(route('customers.quick-store'), [
            'name' => 'Budi Baru',
            'phone' => '081234567890',
        ])
        ->assertOk()
        ->assertJsonFragment(['id' => $lama->id, 'name' => 'Budi Lama']);

    expect(Customer::count())->toBe(1);
});

/**
 * Riwayat booking di halaman pelanggan ikut dibatasi cabang.
 */
test('riwayat booking pelanggan di-scope cabang', function () {
    $cabangA = Branch::factory()->create();
    $cabangB = Branch::factory()->create();
    $customer = Customer::factory()->create();

    $lapanganA = Field::factory()->forBranch($cabangA)->create();
    $lapanganB = Field::factory()->forBranch($cabangB)->create();

    $bookingA = Booking::factory()->forField($lapanganA)->forCustomer($customer)->create();
    Booking::factory()->forField($lapanganB)->forCustomer($customer)->create();

    $kasir = User::factory()->kasir($cabangA)->create();

    $this->actingAs($kasir)
        ->get(route('customers.show', $customer))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('bookings', 1)
            ->where('bookings.0.id', $bookingA->id)
        );
});
