<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            $schema = 'autospec_main';

            DB::statement(sprintf('CREATE SCHEMA IF NOT EXISTS "%s"', $schema));

            DB::statement(sprintf('CREATE TABLE IF NOT EXISTS "%s"."generation_history" (
                id BIGSERIAL PRIMARY KEY,
                run_id VARCHAR(120) NOT NULL UNIQUE,
                user_id BIGINT NULL,
                user_email VARCHAR(255) NULL,
                schema_name VARCHAR(63) NOT NULL,
                prompt TEXT NOT NULL,
                status VARCHAR(32) NOT NULL DEFAULT \'running\',
                error_message TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW()
            )', $schema));

            DB::statement(sprintf('CREATE INDEX IF NOT EXISTS "idx_%s_generation_history_user_id" ON "%s"."generation_history" (user_id)', $schema, $schema));
            DB::statement(sprintf('CREATE INDEX IF NOT EXISTS "idx_%s_generation_history_schema_name" ON "%s"."generation_history" (schema_name)', $schema, $schema));

            return;
        }

        if (! Schema::hasTable('generation_history')) {
            Schema::create('generation_history', function (Blueprint $table) {
                $table->id();
                $table->string('run_id', 120)->unique();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('user_email')->nullable();
                $table->string('schema_name', 63)->index();
                $table->text('prompt');
                $table->string('status', 32)->default('running');
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP TABLE IF EXISTS "autospec_main"."generation_history"');
            DB::statement('DROP SCHEMA IF EXISTS "autospec_main"');

            return;
        }

        Schema::dropIfExists('generation_history');
    }
};
