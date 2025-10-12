<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FacilitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('facilities')->insert([
            // Old 4 facilities
            [
                'name' => 'Library',
                'category' => 'Educational',
                'department' => 'Academics',
                'description' => 'A place where students can read and borrow books.',
                'hours' => '8:00 AM - 5:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Gymnasium',
                'category' => 'Sports',
                'department' => 'Physical Education',
                'description' => 'Indoor sports and fitness facility.',
                'hours' => '6:00 AM - 10:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cafeteria',
                'category' => 'Food & Beverage',
                'department' => 'Student Services',
                'description' => 'Place for students and staff to eat meals.',
                'hours' => '7:00 AM - 7:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Computer Lab',
                'category' => 'Technology',
                'department' => 'ICT Department',
                'description' => 'Lab equipped with computers for student use.',
                'hours' => '8:00 AM - 6:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // New 10 facilities
            [
                'name' => 'Science Laboratory',
                'category' => 'Educational',
                'department' => 'Science Department',
                'description' => 'Laboratory for chemistry, physics, and biology experiments.',
                'hours' => '8:00 AM - 5:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Auditorium',
                'category' => 'Event',
                'department' => 'Administration',
                'description' => 'Large hall for events, assemblies, and presentations.',
                'hours' => '8:00 AM - 9:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Art Studio',
                'category' => 'Creative',
                'department' => 'Arts Department',
                'description' => 'Space for painting, sculpture, and other visual arts activities.',
                'hours' => '8:00 AM - 4:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Music Room',
                'category' => 'Creative',
                'department' => 'Music Department',
                'description' => 'Room equipped for music practice and lessons.',
                'hours' => '9:00 AM - 6:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Conference Room',
                'category' => 'Administration',
                'department' => 'Management',
                'description' => 'Room for meetings and administrative discussions.',
                'hours' => '8:00 AM - 6:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Swimming Pool',
                'category' => 'Sports',
                'department' => 'Physical Education',
                'description' => 'Outdoor swimming facility for student and staff recreation.',
                'hours' => '6:00 AM - 8:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Health Center',
                'category' => 'Medical',
                'department' => 'Student Services',
                'description' => 'Provides medical services and first aid to students and staff.',
                'hours' => '8:00 AM - 5:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Counseling Office',
                'category' => 'Student Support',
                'department' => 'Guidance Office',
                'description' => 'Provides guidance and counseling services for students.',
                'hours' => '8:00 AM - 4:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Basketball Court',
                'category' => 'Sports',
                'department' => 'Physical Education',
                'description' => 'Outdoor court for basketball and other sports activities.',
                'hours' => '6:00 AM - 10:00 PM',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dormitory',
                'category' => 'Accommodation',
                'department' => 'Student Housing',
                'description' => 'On-campus accommodation facility for students.',
                'hours' => '24/7',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
