<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('is_active')->default(true));
        foreach (['teams', 'regions', 'units'] as $name) {
            Schema::create($name, function (Blueprint $t) {
                $t->id();
                $t->string('code', 40)->unique();
                $t->string('name', 160);
                $t->boolean('is_active')->default(true);
                $t->timestamps();
            });
        }
        Schema::create('role_assignments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('role', 20);
            $t->string('scope_type', 20);
            $t->unsignedBigInteger('scope_id')->default(0);
            $t->unique(['user_id', 'role', 'scope_type', 'scope_id'], 'assignment_unique');
        });
        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->string('code', 60)->unique();
            $t->string('title', 220);
            $t->foreignId('team_id')->constrained();
            $t->foreignId('owner_id')->constrained('users');
            $t->unsignedSmallInteger('year');
            $t->date('start_date');
            $t->date('end_date');
            $t->string('indicator', 200);
            $t->string('unit', 80);
            $t->string('value_type', 20);
            $t->unsignedTinyInteger('precision')->default(0);
            $t->string('status', 20)->default('DRAF');
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->index(['year', 'team_id', 'status']);
        });
        Schema::create('activity_regions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->foreignId('region_id')->constrained();
            $t->date('joined_on');
            $t->date('left_on')->nullable();
            $t->unique(['activity_id', 'region_id']);
        });
        Schema::create('periods', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->string('label', 100);
            $t->date('position_date');
            $t->dateTime('due_at');
            $t->string('state', 20)->default('open');
            $t->unsignedInteger('version')->default(0);
            $t->unsignedBigInteger('current_revision_id')->nullable();
            $t->unique(['activity_id', 'position_date']);
            $t->index(['activity_id', 'due_at']);
        });
        Schema::create('target_sets', function (Blueprint $t) {
            $t->id();
            $t->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('version');
            $t->json('data');
            $t->foreignId('actor_id')->constrained('users');
            $t->text('reason')->nullable();
            $t->timestamps();
            $t->unique(['activity_id', 'version']);
        });
        Schema::create('realizations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('period_id')->constrained();
            $t->foreignId('region_id')->constrained();
            $t->decimal('value', 18, 4)->nullable();
            $t->text('notes')->nullable();
            $t->dateTime('first_submitted_at')->nullable();
            $t->foreignId('actor_id')->constrained('users');
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->unique(['period_id', 'region_id']);
        });
        Schema::create('evaluation_revisions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('period_id')->constrained();
            $t->unsignedInteger('revision');
            $t->json('snapshot');
            $t->foreignId('actor_id')->constrained('users');
            $t->timestamps();
            $t->unique(['period_id', 'revision']);
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_id')->constrained('users');
            $t->foreignId('activity_id')->nullable()->constrained();
            $t->foreignId('period_id')->nullable()->constrained();
            $t->foreignId('region_id')->nullable()->constrained();
            $t->string('action', 60);
            $t->json('before_data')->nullable();
            $t->json('after_data')->nullable();
            $t->text('reason')->nullable();
            $t->timestamps();
            $t->index(['activity_id', 'created_at']);
        });
        Schema::create('batch_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained();
            $t->foreignId('period_id')->constrained();
            $t->string('request_key', 100);
            $t->string('payload_hash', 64);
            $t->timestamps();
            $t->unique(['user_id', 'period_id', 'request_key'], 'batch_request_unique');
        });
        Schema::create('batch_results', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_request_id')->constrained()->cascadeOnDelete();
            $t->unsignedInteger('row_index');
            $t->json('result');
            $t->unique(['batch_request_id', 'row_index']);
        });
        Schema::create('correction_requests', function (Blueprint $t) {
            $t->id();
            $t->foreignId('period_id')->constrained();
            $t->foreignId('region_id')->constrained();
            $t->foreignId('actor_id')->constrained('users');
            $t->foreignId('reviewer_id')->nullable()->constrained('users');
            $t->decimal('old_value', 18, 4)->nullable();
            $t->decimal('new_value', 18, 4)->nullable();
            $t->unsignedInteger('expected_version');
            $t->text('reason');
            $t->text('response')->nullable();
            $t->string('document_ref', 160)->nullable();
            $t->string('status', 30)->default('MENUNGGU_REVIEW');
            $t->timestamps();
            $t->index(['period_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['correction_requests', 'batch_results', 'batch_requests', 'audit_logs', 'evaluation_revisions', 'realizations', 'target_sets', 'periods', 'activity_regions', 'activities', 'role_assignments', 'units', 'regions', 'teams'] as $name) {
            Schema::dropIfExists($name);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_active'));
    }
};
