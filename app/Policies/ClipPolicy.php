<?php

namespace App\Policies;

use App\Models\Clip;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ClipPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(?User $user, Clip $clip): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['staff', 'admin']) || (bool) ($user->can_upload_clips ?? false);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Clip $clip): bool
    {
        return in_array($user->role, ['staff', 'admin']) || $clip->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Clip $clip): bool
    {
        return in_array($user->role, ['staff', 'admin']) || $clip->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Clip $clip): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Clip $clip): bool
    {
        return false;
    }
}
