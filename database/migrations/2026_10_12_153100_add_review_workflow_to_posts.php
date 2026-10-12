<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('lock_version')->default(0)->after('id');
            $table->string('image_card_path')->nullable()->after('image_path');
            $table->string('image_full_path')->nullable()->after('image_card_path');
        });

        Schema::create('post_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('lock_version');
            $table->string('title');
            $table->string('excerpt', 500)->nullable();
            $table->text('body');
            $table->string('status');
            $table->timestamp('published_at')->nullable();
            $table->string('image_path')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('tag_ids');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post_revisions');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['lock_version', 'image_card_path', 'image_full_path']);
        });
    }
};
