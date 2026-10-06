<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_approved',
        'google_id',
        'avatar',
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
            'is_approved' => 'boolean',
        ];
    }

    public function assignedTasks()
    {
        if (! Schema::hasTable('task_user')) {
            return $this->belongsToMany(Task::class, 'task_user', 'user_id', 'task_id')->whereRaw('1 = 0')->withTimestamps();
        }

        return $this->belongsToMany(Task::class, 'task_user')->withTimestamps();
    }

    public function directAssignedTasks()
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function tasks()
    {
        return $this->assignedTasks();
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function clientNotes()
    {
        return $this->hasMany(ClientNote::class);
    }

    public function assignedClients()
    {
        return $this->belongsToMany(Client::class, 'client_user')->withTimestamps();
    }

    public function clients()
    {
        return $this->assignedClients();
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
