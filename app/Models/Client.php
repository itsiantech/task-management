<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'status',
    ];

    public function notes()
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

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }
}
