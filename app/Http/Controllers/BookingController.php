<?php

namespace App\Http\Controllers;

use App\Actions\CreateBookingAction;
use App\Actions\CreateBookingData;
use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Exceptions\PriceNotConfiguredException;
use App\Exceptions\SlotUnavailableException;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Field;
use App\Models\User;
use App\Services\AvailabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly CreateBookingAction $createBooking,
    ) {}

    /**
     * Grid ketersediaan — tampilan inti sistem (docs/01-prd.md Modul 5).
     */
    public function grid(Request $request): Response
    {
        Gate::authorize('viewAny', Booking::class);

        $branches = $this->branchOptions($request->user());
        $branchId = $request->integer('branch_id') ?: ($branches[0]['value'] ?? null);
        $date = $request->date('date') ?? Carbon::today();

        abort_if($branchId === null, 404, 'Belum ada cabang.');

        $branch = Branch::findOrFail($branchId);

        // Admin/kasir tidak boleh melihat grid cabang lain.
        abort_unless(
            $request->user()->isOwner() || $request->user()->branch_id === $branch->id,
            403,
        );

        return Inertia::render('bookings/grid', [
            'grid' => $this->availability->grid($branch, $date),
            'branch' => ['id' => $branch->id, 'name' => $branch->name],
            'branches' => $branches,
            'date' => $date->toDateString(),
        ]);
    }

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Booking::class);

        $bookings = Booking::query()
            ->visibleTo($request->user())
            ->select(['id', 'code', 'branch_id', 'field_name', 'customer_name', 'customer_phone', 'booking_date', 'start_time', 'end_time', 'total', 'paid_amount', 'status', 'source'])
            ->when(
                $request->string('search')->value(),
                fn ($q, string $s) => $q->where(fn ($q2) => $q2
                    ->where('code', 'like', "%{$s}%")
                    ->orWhere('customer_name', 'like', "%{$s}%")
                    ->orWhere('customer_phone', 'like', "%{$s}%")
                )
            )
            ->when(
                $request->string('status')->value(),
                fn ($q, string $s) => $q->where('status', $s)
            )
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('bookings/index', [
            'bookings' => $bookings->through(fn (Booking $b): array => [
                'id' => $b->id,
                'code' => $b->code,
                'field_name' => $b->field_name,
                'customer_name' => $b->customer_name,
                'customer_phone' => $b->customer_phone,
                'booking_date' => $b->booking_date->toDateString(),
                'start_time' => substr($b->start_time, 0, 5),
                'end_time' => substr($b->end_time, 0, 5),
                'total' => $b->total,
                'paid_amount' => $b->paid_amount,
                'status' => $b->status->value,
                'source' => $b->source->value,
            ]),
            'query' => $request->only(['search', 'status', 'sort', 'direction']),
            'statuses' => BookingStatus::options(),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Booking::class);

        return Inertia::render('bookings/create', [
            // Slot yang diklik di grid ikut sebagai prefill (US-05).
            'prefill' => [
                'field_id' => $request->integer('field_id') ?: null,
                'booking_date' => $request->string('date')->value() ?: Carbon::today()->toDateString(),
                'start_time' => $request->string('start_time')->value() ?: null,
            ],
            'fields' => $this->fieldOptions($request->user()),
        ]);
    }

    public function store(StoreBookingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $booking = $this->createBooking->execute(new CreateBookingData(
                fieldId: (int) $data['field_id'],
                customerId: (int) $data['customer_id'],
                date: Carbon::parse($data['booking_date']),
                startTime: $data['start_time'],
                durationHours: (int) $data['duration_hours'],
                source: BookingSource::Walkin,
                createdBy: $request->user()->id,
                payFull: (bool) ($data['pay_full'] ?? false),
            ));
        } catch (SlotUnavailableException|PriceNotConfiguredException $e) {
            // Slot direbut orang lain di antara klik grid dan submit, atau
            // harganya dihapus. Keduanya ditampilkan di field jam.
            return back()->withErrors(['start_time' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => "Booking {$booking->code} berhasil dibuat."]);

        return to_route('bookings.show', $booking);
    }

    public function show(Request $request, Booking $booking): Response
    {
        Gate::authorize('view', $booking);

        return Inertia::render('bookings/show', [
            'booking' => [
                'id' => $booking->id,
                'code' => $booking->code,
                'branch_name' => $booking->branch_name,
                'field_name' => $booking->field_name,
                'customer_name' => $booking->customer_name,
                'customer_phone' => $booking->customer_phone,
                'customer_id' => $booking->customer_id,
                'booking_date' => $booking->booking_date->toDateString(),
                'start_time' => substr($booking->start_time, 0, 5),
                'end_time' => substr($booking->end_time, 0, 5),
                'duration_hours' => $booking->duration_hours,
                'price_per_hour' => $booking->price_per_hour,
                'is_member_price' => $booking->is_member_price,
                'subtotal_field' => $booking->subtotal_field,
                'subtotal_addons' => $booking->subtotal_addons,
                'total' => $booking->total,
                'dp_amount' => $booking->dp_amount,
                'paid_amount' => $booking->paid_amount,
                'outstanding' => $booking->outstanding(),
                'status' => $booking->status->value,
                'source' => $booking->source->value,
                'checked_in_at' => $booking->checked_in_at?->toIso8601String(),
                'expired_at' => $booking->expired_at?->toIso8601String(),
            ],
            'canCancel' => $request->user()->can('cancel', $booking),
        ]);
    }

    /**
     * Kasir memverifikasi kedatangan pelanggan (US-06).
     */
    public function checkIn(Request $request, Booking $booking): RedirectResponse
    {
        Gate::authorize('update', $booking);

        if ($booking->outstanding() > 0) {
            return back()->withErrors([
                'check_in' => 'Masih ada sisa tagihan. Selesaikan pelunasan sebelum check-in.',
            ]);
        }

        if (! $booking->status->isPaidAtLeastPartially()) {
            return back()->withErrors([
                'check_in' => 'Booking belum dibayar.',
            ]);
        }

        $booking->update(['checked_in_at' => now()]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Check-in berhasil.']);

        return back();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function branchOptions(User $user): array
    {
        return Branch::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
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
            ->publiclyBookable()
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
