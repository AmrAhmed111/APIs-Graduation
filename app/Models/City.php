<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';

    protected $fillable = ['name'];

    // Relationship with Doctor Model (one to many)
    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'city_id');
    }
}
