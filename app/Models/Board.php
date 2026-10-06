<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    protected $fillable = ['name', 'description', 'created_by', 'is_private'];

    protected function casts(): array
    {
        return ['is_private' => 'boolean'];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Raw pivot rows (BoardMember). */
    public function memberships()
    {
        return $this->hasMany(BoardMember::class);
    }

    /** Users the board is shared with (pivot: role). */
    public function members()
    {
        return $this->belongsToMany(User::class, 'board_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function columns()
    {
        return $this->hasMany(BoardColumn::class)->orderBy('position');
    }

    public function cards()
    {
        return $this->hasMany(BoardCard::class);
    }

    /** Boards the given user is allowed to see. */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('is_private', false)
                ->orWhere('created_by', $user->id)
                ->orWhereHas('memberships', fn (Builder $m) => $m->where('user_id', $user->id));
        });
    }

    /**
     * Effective role of a user on this board: owner, editor, viewer or null (no access).
     */
    public function roleFor(User $user): ?string
    {
        if ($user->isAdmin() || $this->created_by === $user->id) {
            return 'owner';
        }

        $membership = $this->relationLoaded('memberships')
            ? $this->memberships->firstWhere('user_id', $user->id)
            : $this->memberships()->where('user_id', $user->id)->first();

        if ($membership) {
            return $membership->role;
        }

        return $this->is_private ? null : 'viewer';
    }
}
