<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
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

        $notes = Category::query()->create(['name' => 'Notes', 'slug' => 'notes']);
        $guides = Category::query()->create(['name' => 'Guides', 'slug' => 'guides']);
        $tags = collect(['Laravel', 'Writing', 'Design'])->map(
            fn (string $name): Tag => Tag::query()->create([
                'name' => $name,
                'slug' => strtolower($name),
            ]),
        );

        Post::query()->orderBy('id')->each(function (Post $post, int $index) use ($notes, $guides, $tags): void {
            $post->category()->associate($index % 2 === 0 ? $notes : $guides);
            $post->save();
            $post->tags()->sync([
                $tags[$index % 3]->id,
                $tags[($index + 1) % 3]->id,
            ]);
        });
    }
}
