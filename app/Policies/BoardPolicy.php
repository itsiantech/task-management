<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** See the board and its cards (including credentials). */
    public function view(User $user, Board $board): bool
    {
        return $board->roleFor($user) !== null;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Add / edit / move / delete columns and cards. */
    public function contribute(User $user, Board $board): bool
    {
        return in_array($board->roleFor($user), ['owner', 'editor'], true);
    }

    /** Rename, delete and share the board. */
    public function update(User $user, Board $board): bool
    {
        return $board->roleFor($user) === 'owner';
    }

    public function delete(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }

    public function share(User $user, Board $board): bool
    {
        return $this->update($user, $board);
    }
}
