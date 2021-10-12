<?php

namespace Rutatiina\Expense\Traits\Recurring;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Rutatiina\Expense\Models\RecurringExpenseProperty;
use Rutatiina\FinancialAccounting\Traits\Schedule as FinancialAccountingScheduleTrait;

trait Schedule
{
    use FinancialAccountingScheduleTrait;

    /**
     * Execute the console command.
     *
     * @param \Rutatiina\Expense\Traits\Recurring\Schedule $schedule
     * @return boolean
     */
    public function recurringExpenseSchedule($schedule)
    {
        //return true;

        config(['app.scheduled_process' => true]);

        try
        {
            DB::connection('tenant')->getDatabaseName();
            Schema::hasTable((new RecurringExpenseProperty)->getTable());
        }
        catch (\Throwable $e)
        {
            return false;
        }

        //$schedule->call(function () {
        //    Log::info('recurringInvoiceSchedule via trait has been called #updated');
        //})->everyMinute()->runInBackground();

        //the script to process recurring requests

        $tasks = RecurringExpenseProperty::withoutGlobalScopes()
            ->where('status', 'active')
            ->get();

        //Log::info('number of tasks: '.$tasks->count());

        $this->recurringSchedule($schedule, $tasks);

        return true;
    }
}
