<?php

use App\Support\Database\Constraint;
use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sessions within a term (full term, first/second half, intensive courses). Every term has
 * a full-term session; every section belongs to one session; windows about courses
 * (add/drop, withdrawal, grade entry and appeal, course evaluation) belong to a session.
 * docs/decisions/0009-courses-sections-and-sessions.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->jsonb('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_full_term')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestampsTz();

            $table->unique(['term_id', 'code']);
            // Target of composite foreign keys that keep a window or section in its term.
            $table->unique(['id', 'term_id']);
        });

        Constraint::translatable('term_sessions', 'name');
        DB::statement('ALTER TABLE term_sessions ADD CONSTRAINT term_sessions_order_check CHECK (end_date >= start_date)');
        DB::statement('CREATE UNIQUE INDEX term_sessions_single_full_term_unique ON term_sessions (term_id) WHERE is_full_term');

        SqlFile::apply('functions/terms.sql');
        DB::statement(<<<'SQL'
            CREATE TRIGGER term_sessions_before_write_within_term
                BEFORE INSERT OR UPDATE OF term_id, start_date, end_date ON term_sessions
                FOR EACH ROW EXECUTE FUNCTION util.term_sessions_within_term()
            SQL);

        Schema::table('term_windows', function (Blueprint $table) {
            $table->bigInteger('term_session_id')->nullable()->after('term_id');
            $table->index('term_session_id');
        });
        DB::statement('ALTER TABLE term_windows ADD CONSTRAINT term_windows_term_session_id_foreign FOREIGN KEY (term_session_id, term_id) REFERENCES term_sessions (id, term_id) ON DELETE CASCADE');

        // Existing terms get their full-term session; existing course windows move onto it.
        // Values are fixed here (not read from app code) so the migration stays reproducible.
        DB::statement(<<<'SQL'
            INSERT INTO term_sessions (term_id, code, name, start_date, end_date, is_full_term, created_at, updated_at)
            SELECT id, 'FULL', '{"en": "Full term", "ko": "전체 학기", "zh_CN": "全学期"}'::jsonb, start_date, end_date, true, now(), now()
            FROM terms
            SQL);
        DB::update(<<<'SQL'
            UPDATE term_windows AS w SET term_session_id = s.id
            FROM term_sessions AS s
            WHERE s.term_id = w.term_id AND s.is_full_term
              AND w.window_type IN ('add_drop', 'withdrawal', 'grade_entry', 'grade_appeal', 'course_evaluation')
            SQL);
        // Same list as TermWindowType::courseScoped().
        DB::statement(<<<'SQL'
            ALTER TABLE term_windows ADD CONSTRAINT term_windows_session_check CHECK (
                (window_type IN ('add_drop', 'withdrawal', 'grade_entry', 'grade_appeal', 'course_evaluation')) = (term_session_id IS NOT NULL)
            )
            SQL);

        Constraint::audit(['term_sessions']);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE term_windows DROP CONSTRAINT IF EXISTS term_windows_session_check');
        Schema::table('term_windows', fn (Blueprint $table) => $table->dropColumn('term_session_id'));
        Schema::dropIfExists('term_sessions');
    }
};
