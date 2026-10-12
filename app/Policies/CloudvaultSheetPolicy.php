<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Cloudvault\Sheet;
use App\Models\User;

class CloudvaultSheetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(?User $user, Sheet $sheet): bool
    {
        if ($sheet->public_link) {
            return true;
        }

        if (! $user) {
            return false;
        }

        return $sheet->user_id === $user->id
            || $user->isAdmin()
            || $sheet->shareRoleFor($user) !== null;
    }

    public function update(?User $user, Sheet $sheet): bool
    {
        if (! $user) {
            return false;
        }

        if ($sheet->user_id === $user->id || $user->isAdmin()) {
            return true;
        }

        return in_array($sheet->shareRoleFor($user), ['Admin', 'Editor'], true);
    }

    public function manage(?User $user, Sheet $sheet): bool
    {
        if (! $user) {
            return false;
        }

        if ($sheet->user_id === $user->id || $user->isAdmin()) {
            return true;
        }

        return $sheet->shareRoleFor($user) === 'Admin';
    }

    /** Anyone who can view may duplicate (creates their own copy). */
    public function duplicate(?User $user, Sheet $sheet): bool
    {
        return $user !== null && $this->view($user, $sheet);
    }
}
