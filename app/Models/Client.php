<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Accounts\Payment as AccountPayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
        'status',
        'notes',
    ];

    public function notes(): HasMany
    {
        return $this->hasMany(ClientNote::class)->orderBy('interaction_date', 'desc');
    }

    public function assignedMembers()
    {
        return $this->belongsToMany(User::class, 'client_user')->withTimestamps();
    }

    public function members()
    {
        return $this->assignedMembers();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->accountPayments();
    }

    public function accountPayments(): HasMany
    {
        return $this->hasMany(AccountPayment::class)->orderBy('payment_date', 'asc');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function getTotalPaidAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getTotalDueAttribute(): float
    {
        return (float) $this->invoices()->where('status', '!=', 'void')->sum('total_due');
    }

    public function getPendingInvoicesCountAttribute(): int
    {
        return $this->invoices()
            ->whereIn('status', ['unpaid', 'partial', 'due'])
            ->count();
    }
}
