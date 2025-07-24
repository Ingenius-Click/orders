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
        Schema::create('order_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->string('from_status');
            $table->string('to_status');
            $table->boolean('is_enabled')->default(true);
            $table->integer('sort_order')->default(0);
            $table->string('module')->nullable();
            $table->timestamps();

            // Add unique constraint to prevent duplicate transitions
            $table->unique(['from_status', 'to_status']);
        });

        // Insert default transitions
        DB::table('order_status_transitions')->insert([
            [
                'from_status' => 'new',
                'to_status' => 'completed',
                'is_enabled' => true,
                'sort_order' => 10,
                'module' => 'Orders',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'from_status' => 'new',
                'to_status' => 'cancelled',
                'is_enabled' => true,
                'sort_order' => 20,
                'module' => 'Orders',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_transitions');
    }
};
