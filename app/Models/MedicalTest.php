<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalTest extends Model
{
    use HasFactory;

    protected $table = 'medical_tests';

    protected $fillable = [
        'test_name',
        'description',
        'schedule',
        'cost',
    ];
}
