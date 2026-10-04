<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadNote extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'note',
    ];

    public function lead()
    {
        return $this->belongsTo(AssignedLead::class, 'lead_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
