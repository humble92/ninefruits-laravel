<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Course evaluation (legacy SURCATEGORY, SURCONTENT, SURITEM, SURRECORD, which stored the
 * student number with every answer). Completion (who evaluated which enrollment) and the
 * answers are kept apart: responses carry no student, no sequence and no time, and they
 * are not audited row by row (the data audit records the acting user).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_forms', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->smallInteger('scale_points')->default(5);
            $table->jsonb('scale_low_label')->nullable();
            $table->jsonb('scale_high_label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
        });

        Schema::create('evaluation_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluation_form_id')->constrained()->cascadeOnDelete();
            $table->integer('sort_order')->default(0);
            $table->jsonb('category')->nullable();
            $table->jsonb('text');
            $table->string('question_type', 16)->default('scale');
            $table->boolean('is_required')->default(true);
            // Negatively worded: the answer is reversed in averages.
            $table->boolean('is_reversed')->default(false);
            $table->timestampsTz();

            $table->index(['evaluation_form_id', 'sort_order']);
        });

        Schema::create('term_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('evaluation_form_id')->constrained()->restrictOnDelete();
            $table->integer('minimum_responses')->default(5);
            $table->integer('minimum_response_percent')->default(0);
            $table->timestampTz('results_published_at')->nullable();
            $table->foreignId('published_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
        });

        Schema::create('evaluation_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained()->restrictOnDelete();
            $table->timestampTz('completed_at');
        });

        // No student, no sequence, no time: a random id and the section only.
        Schema::create('evaluation_responses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->constrained()->restrictOnDelete();
            $table->foreignId('evaluation_form_id')->constrained()->restrictOnDelete();

            $table->index('section_id');
        });

        Schema::create('evaluation_answers', function (Blueprint $table) {
            $table->foreignUuid('evaluation_response_id')->constrained()->cascadeOnDelete();
            $table->foreignId('evaluation_question_id')->constrained()->restrictOnDelete();
            $table->smallInteger('value')->nullable();
            $table->text('comment')->nullable();

            $table->primary(['evaluation_response_id', 'evaluation_question_id']);
        });

        Schema::table('course_versions', function (Blueprint $table) {
            // Internships and practica can be left out (legacy: course category H).
            $table->boolean('requires_evaluation')->default(true);
        });

        Constraint::translatable('evaluation_forms', 'name');
        Constraint::translatable('evaluation_forms', 'description', nullable: true);
        Constraint::translatable('evaluation_forms', 'scale_low_label', nullable: true);
        Constraint::translatable('evaluation_forms', 'scale_high_label', nullable: true);
        Constraint::translatable('evaluation_questions', 'category', nullable: true);
        Constraint::translatable('evaluation_questions', 'text');

        DB::statement('ALTER TABLE evaluation_forms ADD CONSTRAINT evaluation_forms_scale_points_check CHECK (scale_points BETWEEN 2 AND 10)');
        DB::statement("ALTER TABLE evaluation_questions ADD CONSTRAINT evaluation_questions_type_check CHECK (question_type IN ('scale', 'text'))");
        DB::statement(<<<'SQL'
            ALTER TABLE term_evaluations
                ADD CONSTRAINT term_evaluations_minimum_check CHECK (minimum_responses >= 0 AND minimum_response_percent BETWEEN 0 AND 100)
            SQL);
        DB::statement('ALTER TABLE evaluation_answers ADD CONSTRAINT evaluation_answers_value_check CHECK ((value IS NULL OR value BETWEEN 1 AND 10) AND (value IS NOT NULL OR comment IS NOT NULL))');

        Constraint::audit(['evaluation_forms', 'evaluation_questions', 'term_evaluations']);
    }

    public function down(): void
    {
        Schema::table('course_versions', function (Blueprint $table) {
            $table->dropColumn('requires_evaluation');
        });
        Schema::dropIfExists('evaluation_answers');
        Schema::dropIfExists('evaluation_responses');
        Schema::dropIfExists('evaluation_completions');
        Schema::dropIfExists('term_evaluations');
        Schema::dropIfExists('evaluation_questions');
        Schema::dropIfExists('evaluation_forms');
    }
};
