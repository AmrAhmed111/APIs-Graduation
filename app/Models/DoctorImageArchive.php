<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorImageArchive extends Model
{
    use HasFactory;

    protected $table = 'doctor_image_archives';

    protected $fillable = [
        'image_name',
        'doctor_id',
        'deleted_at',
    ];

    public function doctorArchive()
    {
        return $this->belongsTo(DoctorArchive::class, 'doctor_id');
    }
}
