<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('plant_type');              // narra, sampaguita, ...
            $table->string('personality')->default('cheerful');
            $table->unsignedTinyInteger('hydration')->default(100); // value at state_updated_at
            $table->unsignedTinyInteger('hunger')->default(100);    // value at state_updated_at
            $table->unsignedInteger('xp')->default(0);
            $table->string('decoration')->default('none');
            $table->timestamp('state_updated_at')->useCurrent();
            $table->timestamp('last_played_at')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();         // only set if shared on the map
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
