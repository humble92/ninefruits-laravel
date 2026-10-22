<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Buildings and rooms (legacy BUILDING, ROOM).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('building_id')->constrained()->restrictOnDelete();
            $table->string('code', 32);
            $table->jsonb('name')->nullable();
            $table->foreignId('room_type_id')->nullable()->constrained('lookup_values')->nullOnDelete();
            $table->foreignId('org_unit_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('capacity')->nullable();
            $table->boolean('is_schedulable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->text('note')->nullable();
            $table->timestampsTz();

            $table->unique(['building_id', 'code']);
        });

        Constraint::translatable('buildings', 'name');
        Constraint::translatable('rooms', 'name', nullable: true);
        DB::statement('ALTER TABLE rooms ADD CONSTRAINT rooms_capacity_check CHECK (capacity IS NULL OR capacity >= 0)');

        Constraint::audit(['buildings', 'rooms']);
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('buildings');
    }
};
