<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grn_headers', function (Blueprint $table) {
            $table->string('external_idempotency_key')->nullable()->after('grn_no');
            $table->unique('external_idempotency_key', 'ux_grn_headers_external_idem');
        });
    }

    public function down(): void
    {
        Schema::table('grn_headers', function (Blueprint $table) {
            $table->dropUnique('ux_grn_headers_external_idem');
            $table->dropColumn('external_idempotency_key');
        });
    }
};
