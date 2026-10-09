<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'body',
        'sender_id',
        'target_type',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients()
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'announcement_recipients')->withPivot('read_at')->withTimestamps();
    }
}
