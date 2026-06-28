<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Current plan (NULL is treated as the free tier). Updated by the
            // billing flow / Stripe webhook on subscription changes.
            $table->foreignId('plan_id')->nullable()->after('type')->constrained('plans')->nullOnDelete();
            // Stripe customer id (for Cashier / Checkout), set when billing starts.
            $table->string('stripe_customer_id')->nullable()->after('plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('plan_id');
            $table->dropColumn('stripe_customer_id');
        });
    }
};
