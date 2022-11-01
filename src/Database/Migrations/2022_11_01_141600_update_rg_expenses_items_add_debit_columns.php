<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateRgExpensesItemsAddDebitColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('tenant')->table('rg_expense_items', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->nullable()->after('expense_id');
            $table->unsignedBigInteger('debit_financial_account_code')->nullable()->after('item_id');
            $table->unsignedInteger('quantity')->default(1)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
        Schema::connection('tenant')->table('rg_expense_items', function (Blueprint $table) {
            $table->dropColumn('item_id');
            $table->dropColumn('debit_financial_account_code');
            $table->dropColumn('quantity');
        });
    }
}
