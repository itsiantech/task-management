<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCorrection extends Model
{
    protected $fillable = [
        'task_id',
        'user_id',
        'correction_date',
        'content',
        'status',
    ];

    protected $casts = [
        'correction_date' => 'date',
    ];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
