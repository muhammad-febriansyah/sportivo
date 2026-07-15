<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\Booking;
use App\Models\Customer;
use App\Services\CustomerService;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customers) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Customer::class);

        return Inertia::render('customers/index', [
            'customers' => $this->customers->paginate([
                'search' => $request->string('search')->value() ?: null,
                'member' => $request->string('member')->value() ?: null,
                'sort' => $request->string('sort')->value() ?: null,
                'direction' => $request->string('direction')->value() ?: null,
            ]),
            'query' => $request->only(['search', 'member', 'sort', 'direction']),
            'canManageMembership' => $request->user()->can('manageMembership', new Customer),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Customer::class);

        return Inertia::render('customers/create', [
            'canManageMembership' => request()->user()->can('manageMembership', new Customer),
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        Customer::create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pelanggan berhasil ditambahkan.']);

        return to_route('customers.index');
    }

    public function show(Request $request, Customer $customer): Response
    {
        Gate::authorize('view', $customer);

        return Inertia::render('customers/show', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'is_member' => $customer->is_member,
                'member_until' => $customer->member_until?->toDateString(),
                'is_active_member' => $customer->isActiveMember(),
                'notes' => $customer->notes,
            ],
            // Riwayat booking di-scope cabang: admin/kasir hanya melihat
            // booking pelanggan ini di cabang mereka sendiri.
            'bookings' => Booking::query()
                ->visibleTo($request->user())
                ->where('customer_id', $customer->id)
                ->select(['id', 'code', 'branch_name', 'field_name', 'booking_date', 'start_time', 'end_time', 'total', 'status'])
                ->orderByDesc('booking_date')
                ->orderByDesc('start_time')
                ->limit(50)
                ->get()
                ->map(fn (Booking $b): array => [
                    'id' => $b->id,
                    'code' => $b->code,
                    'branch_name' => $b->branch_name,
                    'field_name' => $b->field_name,
                    'booking_date' => $b->booking_date->toDateString(),
                    'start_time' => substr($b->start_time, 0, 5),
                    'end_time' => substr($b->end_time, 0, 5),
                    'total' => $b->total,
                    'status' => $b->status->value,
                ])
                ->all(),
            'canUpdate' => $request->user()->can('update', $customer),
        ]);
    }

    public function edit(Request $request, Customer $customer): Response
    {
        Gate::authorize('update', $customer);

        return Inertia::render('customers/edit', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'is_member' => $customer->is_member,
                'member_until' => $customer->member_until?->toDateString(),
                'notes' => $customer->notes,
            ],
            'canManageMembership' => $request->user()->can('manageMembership', $customer),
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Pelanggan berhasil diperbarui.']);

        return to_route('customers.index');
    }

    /**
     * Pembuatan pelanggan inline dari form booking walk-in (US-05).
     *
     * Hanya nama + nomor WA agar kasir tidak terhambat; data lain bisa
     * dilengkapi belakangan lewat halaman pelanggan.
     */
    public function quickStore(Request $request): JsonResponse
    {
        Gate::authorize('create', Customer::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        $phone = PhoneNumber::normalize($validated['phone']);

        // Nomor WA adalah identifier unik — kalau sudah ada, kembalikan yang
        // lama alih-alih menolak. Kasir tidak perlu tahu soal duplikat.
        $customer = Customer::firstOrCreate(
            ['phone' => $phone],
            ['name' => $validated['name']],
        );

        return response()->json([
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'is_member' => $customer->isActiveMember(),
        ], $customer->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Pencarian pelanggan untuk form booking walk-in (US-05).
     */
    public function search(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Customer::class);

        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:50'],
        ]);

        return response()->json(
            $this->customers->search($validated['q'])->map(fn (Customer $c): array => [
                'id' => $c->id,
                'name' => $c->name,
                'phone' => $c->phone,
                'is_member' => $c->isActiveMember(),
            ])
        );
    }
}
