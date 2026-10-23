<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Org units with validity periods and reorganization history, and programs
 * (legacy SFCOLLEGE, SFDEPARTMENT). porting/porting-plan.md section 10.4.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('org_unit_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->jsonb('name');
            $table->boolean('is_academic')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();
        });

        Schema::create('org_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->jsonb('name');
            $table->jsonb('short_name')->nullable();
            $table->foreignId('org_unit_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('org_units')->restrictOnDelete();
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->index('parent_id');
        });

        Schema::create('org_unit_successions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('predecessor_org_unit_id')->constrained('org_units')->cascadeOnDelete();
            $table->foreignId('successor_org_unit_id')->constrained('org_units')->cascadeOnDelete();
            $table->string('change_type', 32);
            $table->date('effective_date');
            $table->text('reason')->nullable();
            $table->timestampsTz();

            $table->unique(['predecessor_org_unit_id', 'successor_org_unit_id']);
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64)->unique();
            $table->jsonb('name');
            $table->jsonb('degree_title')->nullable();
            $table->string('degree_level', 32);
            $table->foreignId('org_unit_id')->constrained()->restrictOnDelete();
            $table->decimal('standard_duration_years', 3, 1)->nullable();
            $table->date('effective_start_date');
            $table->date('effective_end_date')->nullable();
            $table->timestampsTz();
        });

        foreach (['org_unit_types' => ['name'], 'org_units' => ['name'], 'programs' => ['name']] as $table => $columns) {
            foreach ($columns as $column) {
                Constraint::translatable($table, $column);
            }
        }
        Constraint::translatable('org_units', 'short_name', nullable: true);
        Constraint::translatable('programs', 'degree_title', nullable: true);

        Constraint::datePeriod('org_units', 'effective_period', 'effective_start_date', 'effective_end_date');
        Constraint::datePeriod('programs', 'effective_period', 'effective_start_date', 'effective_end_date');

        DB::statement('ALTER TABLE org_units ADD CONSTRAINT org_units_not_own_parent_check CHECK (parent_id IS NULL OR parent_id <> id)');
        DB::statement('ALTER TABLE org_unit_successions ADD CONSTRAINT org_unit_successions_distinct_check CHECK (predecessor_org_unit_id <> successor_org_unit_id)');

        Constraint::translationSearchIndex('org_units', 'name');
        Constraint::translationSearchIndex('programs', 'name');

        SqlFile::apply('functions/org_units.sql');
        DB::statement(<<<'SQL'
            CREATE TRIGGER org_units_before_write_prevent_cycle
            BEFORE INSERT OR UPDATE OF parent_id ON org_units
            FOR EACH ROW EXECUTE FUNCTION util.org_units_prevent_cycle()
            SQL);

        Constraint::audit(['org_unit_types', 'org_units', 'org_unit_successions', 'programs']);
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
        Schema::dropIfExists('org_unit_successions');
        Schema::dropIfExists('org_units');
        Schema::dropIfExists('org_unit_types');
    }
};
