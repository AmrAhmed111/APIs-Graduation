<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorInformation extends Model
{
    use HasFactory;

    protected $table = 'doctor_informations';

    protected $fillable = [
        'experience',
        'number_of_patients',
        'about',
        'schedule',
        'salary',
        'doctor_id',
    ];

    // Relationship with Doctor Model
    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}
