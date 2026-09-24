<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Texts of the notifications edited by the administrators, one row per
 * notification, locale and channel. Skipped when the application created
 * the table itself before installing the package.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_messages')) {
            return;
        }

        Schema::create('notification_messages', function (Blueprint $table): void {
            $table->id();
            $table->string('notification_class');
            $table->string('locale');
            $table->string('channel');
            $table->text('subject');
            $table->text('message');
            $table->timestamps();

            $table->unique(['notification_class', 'locale', 'channel'], 'notification_messages_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_messages');
    }
};
