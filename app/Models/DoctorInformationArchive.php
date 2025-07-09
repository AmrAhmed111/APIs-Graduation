<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorInformationArchive extends Model
{
    use HasFactory;

    protected $table = 'doctor_information_archives';

    protected $fillable = [
        'experience',
        'number_of_patients',
        'about',
        'schedule',
        'salary',
        'doctor_id',
        'deleted_at',
    ];

    public function doctorArchive()
    {
        return $this->belongsTo(DoctorArchive::class, 'doctor_id');
    }
}
