<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'first_name' => 'User',
            'last_name' => 'One',
            'email' => 'user.one@tmail.test',
            'username' => 'user1',
            'password' => Hash::make('password'),
        ]);
    }
}