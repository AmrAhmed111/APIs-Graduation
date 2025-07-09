<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Appointment extends Model
{
    use HasApiTokens, HasFactory;

    protected $table = 'appointments';

    protected $fillable = [
        'pat_id',
        'doc_id',
        'appoint_time',
        'appoint_date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'pat_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doc_id');
    }
}
