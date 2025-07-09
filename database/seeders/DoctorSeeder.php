<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = [
            [
                'firstName' => 'asmaa',
                'lastName' => 'mohamed',
                'username' => 'asmaa_mohamed12',
                'email' => 'asmaamohamed@gmail.com',
                'password' => Hash::make('Asmaa$!mohamed%26123'),
                'gender' => 'female',
                'phoneNumber' => '01546237894',
                'rating' => 4.5,
                'spec_id' => 1,
                'user_id' => 1,
                'city_id' => 1,
            ],
            [
                'firstName' => 'ali',
                'lastName' => 'mohamed',
                'username' => 'ali_mohamed12',
                'email' => 'alimohamed@gmail.com',
                'password' => Hash::make('Ali$!mohamed%26123'),
                'gender' => 'male',
                'phoneNumber' => '01234567891',
                'rating' => 3.5,
                'spec_id' => 3,
                'user_id' => 1,
                'city_id' => 10,
            ],
            [
                'firstName' => 'ahmed',
                'lastName' => 'ali',
                'username' => 'ahmed_ali12',
                'email' => 'ahmedali@gmail.com',
                'password' => Hash::make('Ahmed$!ali%26123'),
                'gender' => 'male',
                'phoneNumber' => '01234567891',
                'rating' => 4.9,
                'spec_id' => 2,
                'user_id' => 1,
                'city_id' => 15,
            ],
        ];

        foreach ($doctors as $doctorData) {
            if (! Doctor::where('email', $doctorData['email'])->exists()) {
                $doctor = Doctor::create([
                    'firstName' => $doctorData['firstName'],
                    'lastName' => $doctorData['lastName'],
                    'username' => $doctorData['username'],
                    'email' => $doctorData['email'],
                    'password' => $doctorData['password'],
                    'gender' => $doctorData['gender'],
                    'phoneNumber' => $doctorData['phoneNumber'],
                    'rating' => $doctorData['rating'],
                    'spec_id' => $doctorData['spec_id'],
                    'user_id' => $doctorData['user_id'],
                    'city_id' => $doctorData['city_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $doctor->image()->create([
                    'image_name' => 'images/doctor_img/default.png', // Virtual image if the image does not exist
                ]);
            }
        }
    }
}
