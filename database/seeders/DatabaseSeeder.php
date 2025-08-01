<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $users = collect(
            [
                ['name' => 'Faustino Vasquez', 'email' => 'fvasquez@local.com'],
                ['name' => 'Sebastian Vasquez', 'email' => 'svasquez@local.com']
            ]
        )->map(function ($user) {
            return User::factory()->create([
                'name' => $user['name'],
                'email' => $user['email'],
            ]);
        });

        $groups = collect([
            ['name' => 'Grupo 1', "created_by" => $users[0]->id],
            ['name' => 'Grupo 2', "created_by" => $users[1]->id]
        ])->map(function ($group) {
            return \App\Models\Group::create([
                'name' => $group['name'],
                'created_by' => $group['created_by']
            ]);
        });

        // Relación muchos a muchos: ambos usuarios en ambos grupos
        foreach ($groups as $group) {
            $group->users()->attach([$users[0]->id, $users[1]->id]);
        }


        $categories = collect([
            ['name' => 'Category 1', 'user_id' => $users[0]->id],
            ['name' => 'Category 2', 'user_id' => $users[1]->id]
        ])->map(function ($category) {
            return \App\Models\Category::create([
                'name' => $category['name'],
                'user_id' => $category['user_id']
            ]);
        });

        $credentials = [
            [
                'username' => 'Credential 1',
                'password' => bcrypt('password1'),
                'description' => 'Description for Credential 1',
                'category_id' => $categories[0]->id,
                'user_id' => $users[0]->id
            ],
            [
                'username' => 'Credential 2',
                'password' => bcrypt('password2'),
                'description' => 'Description for Credential 2',
                'category_id' => $categories[1]->id,
                'user_id' => $users[1]->id
            ]
        ];

        $credentials = collect($credentials)->map(function ($credential) {
            return \App\Models\Credential::create([
                'username' => $credential['username'],
                'password' => $credential['password'],
                'description' => $credential['description'],
                'category_id' => $credential['category_id'],
                'user_id' => $credential['user_id']
            ]);
        });

    }
}
