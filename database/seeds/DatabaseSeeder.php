<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('seekerProfile')->insert([
            'name' => Str::random(10),
            'location' => 'Jabalpur',
            'description' => Str::random(100),
            'email' => Str::random(10).'@gmail.com',
            'photo' => Str::random(10),
            'gallery' => Str::random(10),
            'number'=>'8291712173'
        ]);
    }
}
