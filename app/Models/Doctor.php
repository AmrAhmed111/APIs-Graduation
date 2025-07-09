<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Doctor extends Model
{
    use HasApiTokens,HasFactory, Notifiable;

    protected $table = 'doctors';

    protected $fillable = [
        'firstName',
        'lastName',
        'username',
        'email',
        'password',
        'gender',
        'phoneNumber',
        'rating',
        'spec_id',
        'user_id',
        'city_id',
    ];

    protected $hidden = [
        'password',
    ];

    // Relationship with Specialization (if exists)
    public function specialization()
    {
        return $this->belongsTo(Specialization::class, 'spec_id');
    }

    // Relationship with User (if exists)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship with DoctorImage (if exists)
    public function image()
    {
        return $this->hasOne(DoctorImage::class, 'doctor_id');
    }

    // Relationship with DoctorInformation (if exists)
    public function information()
    {
        return $this->hasOne(DoctorInformation::class, 'doctor_id');
    }

    // Relationship with City (if exists)
    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    // Get the patients who favorited this doctor
    public function favoritedByPatients()
    {
        return $this->belongsToMany(Patient::class, 'favorite_doctors', 'doctor_id', 'patient_id')
            ->withTimestamps();
    }
}
