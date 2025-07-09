<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User|Patient|Doctor|null $user): bool
    {
        // Allow owner and Doctor to see all patients
        if ($user instanceof User && $user->role === 'owner') {
            return true;
        }

        // The patient cannot see all patients.
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User|Patient|Doctor|null $user, Patient $patient): bool
    {
        // Allow owner and Doctor to see any patient
        if ($user instanceof User && $user->role === 'owner') {
            return true;
        }
        if ($user instanceof Doctor) {
            return true;
        }
        // The patient only sees his data.
        if ($user instanceof Patient) {
            return $user->id === $patient->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User|Patient|null $user): bool
    {
        return ! $user;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User|Patient|Doctor|null $user, Patient $patient): bool
    {
        // The patient only modifies his data.
        if ($user instanceof Patient) {
            return $user->id === $patient->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User|Patient|Doctor|null $user, Patient $patient): bool
    {
        // Patient disables his account (soft delete)
        if ($user instanceof Patient) {
            return $user->id === $patient->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User|Patient|Doctor|null $user, Patient $patient): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User|Patient|Doctor|null $user, Patient $patient): bool
    {
        return false;
    }
}
