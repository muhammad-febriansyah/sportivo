<?php

namespace App\Models;

use App\Support\PhoneNumber;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $phone
 * @property string|null $email
 * @property string|null $password
 * @property bool $is_member
 * @property Carbon|null $member_until
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable(['name', 'phone', 'email', 'password', 'is_member', 'member_until', 'notes'])]
#[Hidden(['password'])]
class Customer extends Model
{
    /** @use HasFactory<CustomerFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_member' => 'boolean',
            'member_until' => 'date',
            'password' => 'hashed',
        ];
    }

    /**
     * Nomor WA selalu disimpan dalam bentuk 628xxx, apa pun format inputnya.
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => PhoneNumber::normalize($value),
        );
    }

    /**
     * Membership yang kedaluwarsa otomatis kembali ke harga umum — statusnya
     * tidak perlu diubah manual. Lihat docs/01-prd.md Modul 10.
     */
    public function isActiveMember(?Carbon $on = null): bool
    {
        if (! $this->is_member) {
            return false;
        }

        // Tanpa tanggal berakhir, membership berlaku selamanya.
        if ($this->member_until === null) {
            return true;
        }

        return $this->member_until->gte(($on ?? Carbon::today())->startOfDay());
    }

    /**
     * @param  Builder<static>  $query
     */
    #[Scope]
    protected function activeMembers(Builder $query): void
    {
        $query->where('is_member', true)
            ->where(fn (Builder $q) => $q
                ->whereNull('member_until')
                ->orWhere('member_until', '>=', Carbon::today())
            );
    }
}
