<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructors', function (Blueprint $table) {
            $table->foreignId('academic_title_id')->nullable()->constrained('lookup_values')->restrictOnDelete();
            $table->index('academic_title_id');
        });

        DB::statement(<<<'SQL'
            INSERT INTO lookup_types (code, name, is_system, created_at, updated_at)
            VALUES ('academic_title', '{"en":"Academic title","ko":"직위","zh_CN":"职称"}', true, now(), now())
            ON CONFLICT (code) DO UPDATE SET is_system = true
            SQL);

        // Preserve every distinct set of translations without guessing which titles are equivalent.
        DB::statement(<<<'SQL'
            INSERT INTO lookup_values (lookup_type_id, code, name, created_at, updated_at)
            SELECT DISTINCT types.id, 'legacy_' || md5(instructors.academic_title::text), instructors.academic_title, now(), now()
            FROM instructors
            CROSS JOIN lookup_types AS types
            WHERE types.code = 'academic_title' AND instructors.academic_title IS NOT NULL
                AND NOT EXISTS (
                    SELECT 1 FROM lookup_values AS existing
                    WHERE existing.lookup_type_id = types.id AND existing.name = instructors.academic_title
                )
            ON CONFLICT (lookup_type_id, code) DO NOTHING
            SQL);

        DB::statement(<<<'SQL'
            UPDATE instructors
            SET academic_title_id = matches.id
            FROM (
                SELECT DISTINCT ON (titles.name) titles.name, titles.id
                FROM lookup_values AS titles
                JOIN lookup_types AS types ON types.id = titles.lookup_type_id
                WHERE types.code = 'academic_title'
                ORDER BY titles.name, titles.is_active DESC, titles.id
            ) AS matches
            WHERE instructors.academic_title = matches.name
            SQL);

        if (DB::table('instructors')->whereNotNull('academic_title')->whereNull('academic_title_id')->exists()) {
            throw new RuntimeException('An existing academic title could not be assigned a code.');
        }

        Schema::table('instructors', fn (Blueprint $table) => $table->dropColumn('academic_title'));
    }

    public function down(): void
    {
        Schema::table('instructors', fn (Blueprint $table) => $table->jsonb('academic_title')->nullable());
        Constraint::translatable('instructors', 'academic_title', nullable: true);

        DB::statement(<<<'SQL'
            UPDATE instructors
            SET academic_title = lookup_values.name
            FROM lookup_values
            WHERE instructors.academic_title_id = lookup_values.id
            SQL);

        Schema::table('instructors', fn (Blueprint $table) => $table->dropConstrainedForeignId('academic_title_id'));
    }
};
