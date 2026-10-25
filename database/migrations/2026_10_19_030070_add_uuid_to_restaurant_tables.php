<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('code');
        });

        DB::table('restaurant_tables')
            ->whereNull('uuid')
            ->orderBy('id')
            ->each(function (object $table): void {
                DB::table('restaurant_tables')
                    ->where('id', $table->id)
                    ->update(['uuid' => (string) Str::uuid()]);
            });

        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn('uuid');
        });
    }
};
