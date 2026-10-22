<?php

use App\Support\Database\Constraint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts linked to people (one each), external sign-in identities (school SSO),
 * invitations for people without an account, and account lockout
 * (docs/porting-plan.md section 7.1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Display name for accounts without a person; people show their own name.
            $table->string('name')->nullable()->change();
            $table->foreignId('person_id')->nullable()->unique()->constrained('people')->nullOnDelete();
            $table->integer('failed_login_count')->default(0);
            $table->timestampTz('locked_until')->nullable();
        });

        Schema::create('user_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 64);
            $table->string('subject');
            $table->string('email')->nullable();
            $table->timestampTz('linked_at');
            $table->timestampTz('last_used_at')->nullable();
            $table->timestampsTz();

            $table->unique(['provider', 'subject']);
            $table->unique(['user_id', 'provider']);
        });

        Schema::create('account_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('email');
            $table->char('token_hash', 64)->unique();
            $table->timestampTz('expires_at');
            $table->timestampTz('accepted_at')->nullable();
            $table->foreignId('invited_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index('person_id');
        });

        Constraint::audit(['user_identities', 'account_invitations']);
    }

    public function down(): void
    {
        Schema::dropIfExists('account_invitations');
        Schema::dropIfExists('user_identities');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('person_id');
            $table->dropColumn(['failed_login_count', 'locked_until']);
        });
    }
};
