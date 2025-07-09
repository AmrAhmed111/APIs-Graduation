<?php

namespace Database\Seeders;

use App\Models\MedicalTest;
use Illuminate\Database\Seeder;

class MedicalTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define available days of the week, excluding Friday
        $days = ['Saturday', 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];

        // Define available time slots from 10:00 AM to 5:00 PM
        $timeSlots = [
            '10:00 AM', '11:00 AM', '12:00 PM', '1:00 PM',
            '2:00 PM', '3:00 PM', '4:00 PM', '5:00 PM',
        ];

        $medicalTests = [
            ['test_name' => 'Blood Test', 'description' => 'A comprehensive test to analyze blood components.', 'cost' => 100.00, 'created_at' => now(), 'updated_at' => now()],
            ['test_name' => 'Urine Test', 'description' => 'A test to check for infections or kidney issues.', 'cost' => 80.50, 'created_at' => now(), 'updated_at' => now()],
            ['test_name' => 'X-Ray', 'description' => 'Imaging test to diagnose bone fractures or lung issues.', 'cost' => 250.75, 'created_at' => now(), 'updated_at' => now()],
            ['test_name' => 'MRI Scan', 'description' => 'Detailed imaging for internal organs and tissues.', 'cost' => 1200.00, 'created_at' => now(), 'updated_at' => now()],
            ['test_name' => 'Ultrasound', 'description' => 'Sound waves to visualize fetus or internal organs.', 'cost' => 300.25, 'created_at' => now(), 'updated_at' => now()],
            ['test_name' => 'ECG', 'description' => 'Test to monitor heart electrical activity.', 'cost' => 150.00, 'created_at' => now(), 'updated_at' => now()],
            ['test_name' => 'Blood Sugar Test', 'description' => 'Measures glucose levels to diagnose diabetes.', 'cost' => 60.00, 'created_at' => now(), 'updated_at' => now()],
            ['test_name' => 'Lipid Profile', 'description' => 'Checks cholesterol and triglyceride levels.', 'cost' => 200.50, 'created_at' => now(), 'updated_at' => now()],
            ['test_name' => 'Liver Function Test', 'description' => 'Assesses liver health and enzyme levels.', 'cost' => 180.75, 'created_at' => now(), 'updated_at' => now()],
            ['test_name' => 'Kidney Function Test', 'description' => 'Evaluates kidney performance and filtration.', 'cost' => 170.25, 'created_at' => now(), 'updated_at' => now()],
        ];

        // Define fixed day pairs
        $dayPairs = [
            ['Saturday', 'Tuesday'],
            ['Sunday', 'Wednesday'],
            ['Monday', 'Thursday'],
        ];

        // Add schedule to each medical test based on fixed pairs
        foreach ($medicalTests as $index => &$test) {
            // Get the day pair based on the index (cycling through pairs)
            $pairIndex = $index % count($dayPairs);
            $selectedDays = $dayPairs[$pairIndex];

            $schedule = [];
            foreach ($selectedDays as $day) {
                // Select all 8 time slots for each day
                $selectedTimes = $timeSlots;
                $schedule[$day] = $selectedTimes;
            }

            // Convert schedule to JSON
            $test['schedule'] = json_encode($schedule);
        }

        MedicalTest::insert($medicalTests);
    }
}
