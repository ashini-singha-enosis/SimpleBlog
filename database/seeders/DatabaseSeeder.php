<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Ava Admin',
            'email' => 'admin@example.com',
        ]);

        $author = User::factory()->create([
            'name' => 'Noah Writer',
            'email' => 'author@example.com',
        ]);

        Post::factory()->count(6)->published()->for($author, 'author')->create();
        Post::factory()->count(4)->published()->for($admin, 'author')->create();
        Post::factory()->count(2)->draft()->for($author, 'author')->create();
        Post::factory()->scheduled()->for($admin, 'author')->create();
    }
}
