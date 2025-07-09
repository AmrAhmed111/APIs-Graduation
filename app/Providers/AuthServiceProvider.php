<?php

namespace App\Providers;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Policies\PatientPolicy;
use App\Policies\UserPolicy;
// use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

// use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
        Patient::class => PatientPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Privileges on doctors
        $this->defineAddDoctorsGate();
        $this->defineUpdateDoctorGate();
        $this->defineDeleteDoctorGate();
        $this->defineViewDoctorsGate();
        $this->defineViewDoctorGate();

        // Privileges on patients
        $this->defineAddPatientsGate();
    }

    // Gate to verify the validity of adding doctors
    protected function defineAddDoctorsGate(): void
    {
        Gate::define('add-doctors', function ($user) {
            // If the user is of type User and his role is owner
            if ($user instanceof User && $user->role === 'owner') {
                return true;
            }
            if ($user instanceof Doctor) {
                return false;
            }

            // If the user is a Doctor, he is not allowed to add doctors
            return false;
        });
    }

    // Gate to verify the validity of updating doctors
    protected function defineUpdateDoctorGate(): void
    {
        Gate::define('update-doctor', function ($user, Doctor $doctor) {
            // If the user is Owner (of type User and role is owner)
            if ($user instanceof User && $user->role === 'owner') {
                return true;
            }
            // If the user is Doctor only he can edit his data
            if ($user instanceof Doctor) {
                return $user->id === $doctor->id;
            }

            return false;
        });
    }

    // Gate to verify the validity of deleteing doctors
    protected function defineDeleteDoctorGate(): void
    {
        Gate::define('delete-doctor', function ($user, Doctor $doctor) {
            // If the user is an owner
            if ($user instanceof User && $user->role === 'owner') {
                return true;
            }

            // A doctor cannot delete himself or any other doctor
            return false;
        });
    }

    // Gate to verify the validity of seeing all doctors
    protected function defineViewDoctorsGate(): void
    {
        Gate::define('view-doctors', function ($user) {
            // return $user->user->role === 'owner';
            return $user->role === 'owner';
        });
    }

    // Gate to verify the validity of seeing a specific doctor
    protected function defineViewDoctorGate(): void
    {
        Gate::define('view-doctor', function ($user, Doctor $doctor) {
            // Check if the authenticated user is a User (owner)
            if ($user instanceof User) {
                return true;
            }
            // Check if the authenticated user is a Doctor viewing their own profile
            if ($user instanceof Doctor) {
                return $user->id === $doctor->id;
            }

            return false;
        });
    }

    // Gate to verify that the user is not a doctor or owner
    protected function defineAddPatientsGate(): void
    {
        Gate::define('create-patient', function ($user = null) {
            return ! $user;
        });
    }
}
