<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();

            $table->string('group');
            $table->string('name');
            $table->boolean('locked')->default(false);
            $table->jsonb('payload');

            $table->timestampsTz();

            $table->unique(['group', 'name']);
        });

        DB::statement("SELECT util.enable_audit('settings')");
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
