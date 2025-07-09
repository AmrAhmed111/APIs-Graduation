<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Specialization extends Model
{
    use HasApiTokens,HasFactory;

    protected $table = 'specializations';

    protected $fillable = ['name'];

    // Relationship with Doctors
    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'spec_id');
    }
}
