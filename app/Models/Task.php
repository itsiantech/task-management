<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class Task extends Model
{
    protected $fillable = [
        'title',
        'description',
        'sticky_note',
        'status',
        'start_date',
        'due_date',
        'priority',
        'tags',
        'assigned_to',
        'created_by',
        'attachment_path',
        'total_budget',
        'paid_amount',
        'due_amount',
        'payment_status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'tags' => 'array',
        'total_budget' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
    ];

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        if (! Schema::hasTable('task_user')) {
            return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')->whereRaw('1 = 0')->withTimestamps();
        }

        return $this->belongsToMany(User::class, 'task_user')->withTimestamps();
    }

    public function users()
    {
        return $this->members();
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class)->latest();
    }

    public function corrections()
    {
        return $this->hasMany(TaskCorrection::class)->orderByDesc('correction_date')->orderByDesc('created_at');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function attachmentUrl(): ?string
    {
        if (! $this->attachment_path) {
            return null;
        }

        return Storage::disk('public')->url($this->attachment_path);
    }

    public function syncMembers(?array $memberIds): void
    {
        if (! Schema::hasTable('task_user')) {
            return;
        }

        $memberIds = array_values(array_unique(array_filter($memberIds ?? [], fn ($id) => $id !== null && $id !== '')));

        $this->members()->sync($memberIds);
    }

    public function updateFinancials(): void
    {
        $this->due_amount = max(0, (float) ($this->total_budget ?? 0) - (float) ($this->paid_amount ?? 0));

        if ((float) ($this->paid_amount ?? 0) <= 0) {
            $this->payment_status = 'pending';
        } elseif ((float) ($this->due_amount ?? 0) <= 0 && (float) ($this->total_budget ?? 0) > 0) {
            $this->payment_status = 'complete';
        } elseif ((float) ($this->paid_amount ?? 0) > 0 && (float) ($this->due_amount ?? 0) > 0) {
            $this->payment_status = 'partial';
        }

        $this->saveQuietly();
    }
}
