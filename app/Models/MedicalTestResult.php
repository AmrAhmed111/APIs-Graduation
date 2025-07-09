<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalTestResult extends Model
{
    use HasFactory;

    protected $table = 'medical_test_results';

    protected $fillable = [
        'appointment_id',
        'pat_id',
        'doc_id',
        'result_file',
        'notes',
    ];

    public function appointment()
    {
        return $this->belongsTo(MedicalTestAppointment::class, 'appointment_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'pat_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doc_id');
    }
}
