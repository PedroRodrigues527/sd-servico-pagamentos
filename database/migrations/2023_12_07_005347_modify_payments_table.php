<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         // Add a new column
         Schema::table('payments', function (Blueprint $table) {
            $table->string('entity')->nullable();
            $table->string('reference')->nullable();
        });

        // Rename an existing column
        Schema::table('payments', function (Blueprint $table) {
            $table->string('observation')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
       
    }
};
