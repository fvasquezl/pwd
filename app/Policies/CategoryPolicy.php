<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CategoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Category $category): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Category $category, array $attributes = []): bool
    {
        // Solo permite actualizar si el user_id no cambia o sigue siendo el mismo usuario
        if (isset($attributes['user_id']) && $attributes['user_id'] != $user->id) {
            return false;
        }
        return $category->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Category $category, array $attributes = []): bool
    {
        // Solo permite actualizar si el user_id no cambia o sigue siendo el mismo usuario
        if (isset($attributes['user_id']) && $attributes['user_id'] != $user->id) {
            return false;
        }
        return $category->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Category $category): bool
    {
        return true;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Category $category): bool
    {
        return true;
    }
}
