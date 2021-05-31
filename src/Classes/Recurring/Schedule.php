<?php

namespace Rutatiina\Expense\Classes\Recurring;

use Illuminate\Support\Facades\Log;
use Rutatiina\Expense\Models\Expense;
use Rutatiina\Expense\Classes\Recurring\Copy as RecurringExpenseCopy;
use Rutatiina\Expense\Classes\Store as ExpenseStore;

class Schedule
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

        //get the last invoice number
        $txn = Expense::orderBy('id', 'desc')->first();
        //$settings = Setting::first();
        //$number = $settings->number_prefix.(str_pad((optional($txn)->number+1), $settings->minimum_number_length, "0", STR_PAD_LEFT)).$settings->number_postfix;

        $TxnCopy = new RecurringExpenseCopy();
        $txnAttributes = $TxnCopy->run($task->expense_recurring_id);
        $txnAttributes['number'] = (optional($txn)->number+1);
        //Log::info('doc number #'.$txnAttributes['number']);

        $TxnStore = new ExpenseStore();
        $TxnStore->txnInsertData = $txnAttributes;
        $insert = $TxnStore->run();

        if ($insert == false)
        {
            Log::warning('Error: Recurring expense id:: #'.$task->recurring_invoice_id.' failed @ '.\Carbon\Carbon::now());
            Log::warning($TxnStore->errors);
        }
        else
        {
            $task->update(['last_run' => now()]);
            Log::info('Success: Recurring expense id:: #'.$task->recurring_invoice_id.' passed @ '.\Carbon\Carbon::now());
        }
    }
}
