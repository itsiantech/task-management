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
        'member_code',
        'phone',
        'family_phone',
        'present_address',
        'permanent_address',
        'nid_path',
        'cv_path',
        'monthly_salary',
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
            'monthly_salary' => 'decimal:2',
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

    public function announcements()
    {
        return $this->belongsToMany(Announcement::class, 'announcement_recipients')->withPivot('read_at')->withTimestamps();
    }

    public function announcementRecipients()
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    public static function nextMemberCode(): string
    {
        $max = (int) static::query()
            ->whereNotNull('member_code')
            ->where('member_code', 'like', 'M-%')
            ->selectRaw('MAX(CAST(SUBSTR(member_code, 3) AS INTEGER)) as max_code')
            ->value('max_code');

        return 'M-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
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
