<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Course categories and grade scales (legacy SFOBJDIST, SSCOREDEF).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            $table->foreignId('parent_id')->nullable()->constrained('course_categories')->restrictOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('grade_scales', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            $table->decimal('max_grade_points', 4, 2);
            $table->boolean('is_default')->default(false);
            $table->timestampsTz();
        });

        Schema::create('grade_scale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grade_scale_id')->constrained()->cascadeOnDelete();
            $table->string('code', 16);
            $table->jsonb('name')->nullable();
            $table->decimal('grade_points', 4, 2)->nullable();
            $table->decimal('min_percentage', 5, 2)->nullable();
            $table->decimal('max_percentage', 5, 2)->nullable();
            $table->boolean('counts_in_gpa')->default(true);
            $table->boolean('is_passing')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->unique(['grade_scale_id', 'code']);
        });

        Constraint::translatable('course_categories', 'name');
        Constraint::translatable('grade_scales', 'name');
        Constraint::translatable('grade_scale_items', 'name', nullable: true);

        DB::statement('CREATE UNIQUE INDEX grade_scales_single_default_unique ON grade_scales (is_default) WHERE is_default');
        DB::statement('ALTER TABLE course_categories ADD CONSTRAINT course_categories_not_own_parent_check CHECK (parent_id IS NULL OR parent_id <> id)');
        DB::statement(<<<'SQL'
            ALTER TABLE grade_scale_items
                ADD CONSTRAINT grade_scale_items_points_check CHECK (grade_points IS NULL OR grade_points >= 0),
                ADD CONSTRAINT grade_scale_items_percentage_check CHECK (
                    (min_percentage IS NULL OR min_percentage BETWEEN 0 AND 100)
                    AND (max_percentage IS NULL OR max_percentage BETWEEN 0 AND 100)
                    AND (min_percentage IS NULL OR max_percentage IS NULL OR min_percentage <= max_percentage)
                ),
                ADD CONSTRAINT grade_scale_items_gpa_points_check CHECK (NOT counts_in_gpa OR grade_points IS NOT NULL)
            SQL);

        Constraint::audit(['course_categories', 'grade_scales', 'grade_scale_items']);
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_scale_items');
        Schema::dropIfExists('grade_scales');
        Schema::dropIfExists('course_categories');
    }
};
