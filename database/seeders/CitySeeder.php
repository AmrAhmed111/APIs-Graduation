<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cities = [
            ['name' => 'Cairo', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Giza', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Alexandria', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Beheira', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gharbia', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Dakahlia', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sharqia', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Aswan', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Luxor', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mansoura', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tanta', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Suez', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Monufia', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Port Said', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ismailia', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Fayoum', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Qalyubia', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Minya', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Assiut', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sohag', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Qena', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Beni Suef', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Damietta', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kafr El Sheikh', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Zagazig', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Hurghada', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Matrouh', 'created_at' => now(), 'updated_at' => now()],
        ];
        City::insert($cities);
    }
}
