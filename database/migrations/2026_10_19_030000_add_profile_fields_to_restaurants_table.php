<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
            $table->string('phone')->nullable()->after('description');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('logo_path')->nullable()->after('whatsapp');
            $table->string('primary_color', 7)->nullable()->after('logo_path');
            $table->string('secondary_color', 7)->nullable()->after('primary_color');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'phone',
                'whatsapp',
                'logo_path',
                'primary_color',
                'secondary_color',
            ]);
        });
    }
};
