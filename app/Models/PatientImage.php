<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientImage extends Model
{
    use HasFactory;

    protected $table = 'patient_images';

    protected $fillable = [
        'image_name',
        'patient_id',
    ];

    // Define the relationship with the Patient model
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
