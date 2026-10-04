<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedLead extends Model
{
    protected $fillable = [
        'name',
        'lead_manager_id',
        'company',
        'phone',
        'email',
        'source',
        'assigned_to',
        'status',
        'last_contact_date',
        'next_follow_up_date',
    ];

    protected $casts = [
        'last_contact_date' => 'datetime',
        'next_follow_up_date' => 'datetime',
    ];

    public function leadManager()
    {
        return $this->belongsTo(User::class, 'lead_manager_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedMembers()
    {
        return $this->belongsToMany(User::class, 'assigned_lead_user')->withTimestamps();
    }

    public function notes()
    {
        return $this->hasMany(LeadNote::class, 'lead_id')->orderBy('created_at', 'desc');
    }

    public function latestNote()
    {
        return $this->hasOne(LeadNote::class, 'lead_id')->latestOfMany();
    }

    public function getLastNoteAttribute(): ?string
    {
        return $this->latestNote?->note;
    }
}
