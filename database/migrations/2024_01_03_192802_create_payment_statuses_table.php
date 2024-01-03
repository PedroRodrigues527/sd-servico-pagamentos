<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('payment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('payment_statuses')->insert([
            ['name' => 'pendente'],
            ['name' => 'pago'],
            ['name' => 'cancelado'],
            ['name' => 'expirado']
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('payment_statuses');
    }
}
