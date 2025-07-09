<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specializations = [
            ['name' => 'Cardiology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Neurology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Orthopedics', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pediatrics', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dermatology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ophthalmology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gastroenterology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Psychiatry', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Oncology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Endocrinology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Urology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gynecology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Surgery', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dentistry', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Rheumatology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ear, Nose and Throat', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pulmonology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nephrology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hematology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Allergy', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Nutrition', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Anesthesiology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Radiology', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Physical Therapy', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Occupational Therapy', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Speech Therapy', 'created_at' => now(), 'updated_at' => now()],
        ];
        Specialization::insert($specializations);
    }
}
