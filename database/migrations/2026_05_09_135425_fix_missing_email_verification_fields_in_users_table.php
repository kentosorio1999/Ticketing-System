<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'email_verification_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('email_verification_code')->nullable()->after('email');
            });
        }

        if (! Schema::hasColumn('users', 'email_verification_code_expires_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('email_verification_code_expires_at')->nullable()->after('email_verification_code');
            });
        }
    }

    public function down(): void
    {
        // Do nothing to avoid accidentally deleting existing data.
    }
};