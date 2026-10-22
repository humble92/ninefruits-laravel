<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Academic years, terms, term windows and time slots (legacy SFSEMESTER, SSOBJDATE, TTTIME).
 * No global "current term" flag: TermResolver answers per business question
 * (docs/porting-plan.md section 10.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            $table->boolean('is_regular')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->timestampsTz();
        });

        Schema::create('terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_type_id')->constrained()->restrictOnDelete();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 32)->default('planned');
            $table->timestampsTz();

            $table->unique(['academic_year_id', 'term_type_id']);
        });

        Schema::create('term_windows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('window_type', 64);
            $table->jsonb('name')->nullable();
            $table->timestampTz('opens_at');
            $table->timestampTz('closes_at');
            $table->jsonb('eligibility')->nullable();
            $table->timestampsTz();

            $table->index(['window_type', 'opens_at', 'closes_at']);
        });

        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->jsonb('weekdays');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        foreach (['term_types', 'academic_years', 'terms', 'time_slots'] as $table) {
            Constraint::translatable($table, 'name');
        }
        Constraint::translatable('term_windows', 'name', nullable: true);

        Constraint::datePeriod('academic_years', 'year_period', 'start_date', 'end_date');
        Constraint::datePeriod('terms', 'instruction_period', 'start_date', 'end_date');
        Constraint::noOverlap('academic_years', 'year_period');
        Constraint::noOverlap('terms', 'instruction_period', ['term_type_id']);

        DB::statement('ALTER TABLE term_windows ADD CONSTRAINT term_windows_order_check CHECK (closes_at > opens_at)');
        DB::statement('ALTER TABLE time_slots ADD CONSTRAINT time_slots_order_check CHECK (end_time > start_time)');
        DB::statement("ALTER TABLE time_slots ADD CONSTRAINT time_slots_weekdays_array_check CHECK (jsonb_typeof(weekdays) = 'array')");

        Constraint::audit(['term_types', 'academic_years', 'terms', 'term_windows', 'time_slots']);
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
        Schema::dropIfExists('term_windows');
        Schema::dropIfExists('terms');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('term_types');
    }
};
