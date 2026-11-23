<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20);
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['order_id', 'changed_at']);
        });

        DB::table('orders')
            ->select(['id', 'status', 'placed_at', 'created_at'])
            ->orderBy('id')
            ->each(function (object $order): void {
                $timestamp = $order->placed_at ?? $order->created_at;

                DB::table('order_status_histories')->insert([
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'changed_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
