<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Certificates (legacy aca3/cti_*, all_score*, all_biye*: printed from screens, no ledger
 * and no verification): kinds with a template per language that the institution edits,
 * an issue ledger with serial numbers, the stored PDF and a verification code anyone can
 * check on a public page.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->jsonb('name');
            // Who it is for: enrolled students, graduates, or any student record.
            $table->string('audience', 16)->default('any');
            // The body gets a table of the finalized grades (transcript).
            $table->boolean('has_grades')->default(false);
            $table->boolean('has_photo')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_type_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('title', 160);
            // Paragraphs with {placeholders}; see CertificateRenderer::PLACEHOLDERS.
            $table->text('body');
            $table->text('footer')->nullable();
            // E.g. "President of Ninefruits University", printed above the seal.
            $table->string('signer', 160)->nullable();
            $table->timestampsTz();

            $table->unique(['certificate_type_id', 'locale']);
        });

        Schema::create('certificate_number_sequences', function (Blueprint $table) {
            $table->string('prefix', 64)->primary();
            $table->bigInteger('last_value')->default(0);
            $table->timestampsTz();
        });

        Schema::create('certificate_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_record_id')->constrained()->restrictOnDelete();
            $table->foreignId('certificate_type_id')->constrained()->restrictOnDelete();
            $table->string('locale', 8);
            $table->string('serial_number', 64)->unique();
            // Shown on the public verification page; long and random.
            $table->string('verification_code', 40)->unique();
            $table->string('purpose', 200)->nullable();
            $table->smallInteger('copies')->default(1);
            $table->string('status', 16)->default('issued');
            $table->timestampTz('issued_at');
            $table->foreignId('issued_by_id')->nullable()->constrained('users')->nullOnDelete();
            // SHA-256 of the stored PDF, so a presented file can be checked against it.
            $table->string('pdf_checksum', 64)->nullable();
            $table->timestampTz('revoked_at')->nullable();
            $table->foreignId('revoked_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('revocation_reason')->nullable();
            $table->timestampsTz();

            $table->index(['student_record_id', 'issued_at']);
        });

        Constraint::translatable('certificate_types', 'name');

        DB::statement(<<<'SQL'
            ALTER TABLE certificate_types
                ADD CONSTRAINT certificate_types_audience_check CHECK (audience IN ('any', 'enrolled', 'graduated'))
            SQL);
        DB::statement(<<<'SQL'
            ALTER TABLE certificate_issues
                ADD CONSTRAINT certificate_issues_status_check CHECK (status IN ('issued', 'revoked')),
                ADD CONSTRAINT certificate_issues_copies_check CHECK (copies BETWEEN 1 AND 20),
                ADD CONSTRAINT certificate_issues_revoked_check CHECK ((status = 'revoked') = (revoked_at IS NOT NULL))
            SQL);

        Constraint::audit(['certificate_types', 'certificate_templates', 'certificate_issues']);
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_issues');
        Schema::dropIfExists('certificate_number_sequences');
        Schema::dropIfExists('certificate_templates');
        Schema::dropIfExists('certificate_types');
    }
};
