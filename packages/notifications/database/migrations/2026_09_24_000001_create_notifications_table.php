<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Replaces Laravel's `notifications` table: the payload is spread in columns
 * (text, url, path, model) instead of the `data` json. Skipped when the
 * application created the table itself before installing the package.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();

            $table->string('type');

            $table->morphs('notifiable');
            $table->nullableMorphs('model');

            $table->text('text');
            $table->text('path')->nullable();
            $table->text('url')->nullable();

            $table->timestamp('read_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
