<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorArchive extends Model
{
    use HasFactory;

    protected $table = 'doctor_archives';

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
        'deleted_at',
    ];

    public function specialization()
    {
        return $this->belongsTo(Specialization::class, 'spec_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
