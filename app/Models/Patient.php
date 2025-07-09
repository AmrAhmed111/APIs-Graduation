<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// class Patient extends Model
class Patient extends Model
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table = 'patients';

    protected $fillable = [
        'fullName',
        'email',
        'password',
        'DateofBirth',
        'gender',
        'phoneNumber',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Allowed values
    public static $genders = [
        'Male',
        'Female',
    ];

    protected static function boot()
    {
        parent::boot();

        // Delete all tokens associated with the user when the user is deleted
        static::deleting(function ($patient) {
            $patient->tokens()->delete();
        });
    }

    // Get the favorite doctors for the patient
    public function favoriteDoctors()
    {
        return $this->belongsToMany(Doctor::class, 'favorite_doctors', 'patient_id', 'doctor_id')
            ->withTimestamps();
    }

    // Get the medical test appointments for the patient
    public function medicalTestAppointments()
    {
        return $this->hasMany(MedicalTestAppointment::class, 'pat_id');
    }

    // Get the patient images
    public function image()
    {
        return $this->hasOne(PatientImage::class, 'patient_id');
    }
}
