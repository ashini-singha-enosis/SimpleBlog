<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('caption');
            $table->string('slug')->unique();
            $table->string('video_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable');
            $table->text('body');
            $table->timestamps();
        });

        if (Schema::hasTable('reactions')) {
            DB::table('reactions')
                ->where('reactable_type', 'App\\Models\\Post')
                ->update(['reactable_type' => 'post']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('reactions')) {
            DB::table('reactions')
                ->where('reactable_type', 'post')
                ->update(['reactable_type' => 'App\\Models\\Post']);
        }

        Schema::dropIfExists('comments');
        Schema::dropIfExists('videos');
    }
};
