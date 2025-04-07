<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    /** @use HasFactory<\Database\Factories\UserFactory> */

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'credit_balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'credit_balance' => 'decimal:2',
        ];
    }

    /**
     * Check if user has sufficient credit for a purchase
     *
     * @param float $amount
     * @return bool
     */
    public function hasSufficientCredit(float $amount): bool
    {
        return $this->credit_balance >= $amount && $amount > 0;
    }

    /**
     * Deduct credit from user's balance
     *
     * @param float $amount
     * @return bool
     */
    public function deductCredit(float $amount): bool
    {
        if (!$this->hasSufficientCredit($amount)) {
            return false;
        }

        $this->credit_balance -= $amount;
        return $this->save();
    }

    /**
     * Get the current credit balance
     *
     * @return float
     */
    public function getCreditBalance(): float
    {
        return $this->credit_balance;
    }
}
