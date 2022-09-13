<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRgExpensesContactColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->table('rg_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->change();
            $table->string('contact_name', 50)->nullable()->change();
            $table->string('contact_address', 50)->nullable()->change();
        });

        Schema::connection('tenant')->table('rg_expense_recurring_expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->change();
            $table->string('contact_name', 50)->nullable()->change();
            $table->string('contact_address', 50)->nullable()->change();
        });

        Schema::connection('tenant')->table('rg_expense_ledgers', function (Blueprint $table) {
            $table->unsignedBigInteger('contact_id')->nullable()->change();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //do nothing
    }
}
