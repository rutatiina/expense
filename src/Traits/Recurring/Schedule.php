<?php

namespace Rutatiina\Expense\Traits\Recurring;

use Illuminate\Support\Facades\Log;
use Rutatiina\Expense\Models\ExpenseRecurringProperty;

trait Schedule
{
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

        //$schedule->call(function () {
        //    Log::info('recurringInvoiceSchedule via trait has been called #updated');
        //})->everyMinute()->runInBackground();

        //the script to process recurring requests

        $tasks = ExpenseRecurringProperty::withoutGlobalScopes()
            ->where('status', 'active')
            ->get();

        //Log::info('number of tasks: '.$tasks->count());

        $this->recurringSchedule($schedule, $tasks);

        return true;
    }
}
