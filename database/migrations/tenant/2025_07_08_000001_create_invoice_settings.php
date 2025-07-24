<?php

use Illuminate\Database\Migrations\Migration;
use Ingenius\Core\Facades\Settings;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create default invoice settings
        Settings::set('invoices', 'auto_create', true);
        Settings::set('invoices', 'create_on_status', 'completed');
        Settings::set('invoices', 'fallback_status', 'completed');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove invoice settings
        Settings::forget('invoices', 'auto_create');
        Settings::forget('invoices', 'create_on_status');
        Settings::forget('invoices', 'fallback_status');
    }
};
