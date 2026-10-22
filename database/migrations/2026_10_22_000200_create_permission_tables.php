<?php

use App\Support\Database\SqlFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permission catalog and roles (spatie/laravel-permission tables, without teams).
 *
 * Users receive roles only through role_assignments (scope and validity period),
 * see App\Domain\Security\Services\AuthorizationService. model_has_roles and
 * model_has_permissions are kept for package compatibility and stay empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestampsTz();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->jsonb('display_name');
            $table->jsonb('description')->nullable();
            $table->string('persona')->nullable()->unique();
            $table->boolean('is_system')->default(false);
            $table->timestampsTz();

            $table->unique(['name', 'guard_name']);
        });

        DB::statement("ALTER TABLE roles ADD CONSTRAINT roles_display_name_object_check CHECK (jsonb_typeof(display_name) = 'object')");

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');

            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();

            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('role_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('scope_type', 32);
            $table->unsignedBigInteger('scope_id')->nullable();
            $table->timestampTz('starts_at')->nullable();
            $table->timestampTz('ends_at')->nullable();
            $table->text('reason')->nullable();
            $table->foreignId('assigned_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['user_id', 'role_id']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE role_assignments
                ADD CONSTRAINT role_assignments_scope_check
                    CHECK ((scope_type = 'global') = (scope_id IS NULL)),
                ADD CONSTRAINT role_assignments_period_check
                    CHECK (starts_at IS NULL OR ends_at IS NULL OR starts_at < ends_at)
            SQL);

        foreach (['permissions', 'roles', 'role_has_permissions', 'role_assignments'] as $table) {
            DB::statement("SELECT util.enable_audit('{$table}')");
        }

        SqlFile::apply('functions/authorization.sql');
    }

    public function down(): void
    {
        Schema::dropIfExists('role_assignments');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }
};
