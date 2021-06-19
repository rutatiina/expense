<?php

namespace Rutatiina\Expense\Services;

use Illuminate\Support\Facades\Log;

class RecurringExpenseSheduleService
{
    public $task;

    function __construct($task)
    {
        $this->task = $task;
    }

    /**
     * Execute the console command.
     *
     * @return boolean
     */

    public function __invoke()
    {
        $task = $this->task;

        $txnAttributes = RecurringExpenseService::copy($task->recurring_expense_id);
        //Log::info('doc number #'.$txnAttributes['number']);

        $insert = RecurringExpenseService::store($txnAttributes);

        if ($insert == false)
        {
            Log::warning('Error: Recurring expense id:: #'.$task->recurring_invoice_id.' failed @ '.\Carbon\Carbon::now());
            Log::warning(implode("\n", RecurringExpenseService::$errors));
        }
        else
        {
            $task->update(['last_run' => now()]);
            Log::info('Success: Recurring expense id:: #'.$task->recurring_invoice_id.' passed @ '.\Carbon\Carbon::now());
        }
    }
}