<?php

namespace Rutatiina\Expense\Classes\Recurring;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Rutatiina\Expense\Models\ExpenseRecurring;
use Rutatiina\Expense\Models\ExpenseRecurringItem;
use Rutatiina\Expense\Models\ExpenseRecurringItemTax;
use Rutatiina\Expense\Models\ExpenseRecurringLedger;
use Rutatiina\Expense\Models\ExpenseRecurringProperty;
use Rutatiina\Expense\Traits\Recurring\Init as TxnTraitsInit;
use Rutatiina\Expense\Traits\Recurring\Inventory as TxnTraitsInventory;
use Rutatiina\Expense\Traits\Recurring\TxnItemsContactsIdsLedgers as TxnTraitsTxnItemsContactsIdsLedgers;
use Rutatiina\Expense\Traits\Recurring\TxnItemsJournalLedgers as TxnTraitsTxnItemsJournalLedgers;
use Rutatiina\Expense\Traits\Recurring\TxnTypeBasedSpecifics as TxnTraitsTxnTypeBasedSpecifics;
use Rutatiina\Expense\Traits\Recurring\Validate as TxnTraitsValidate;
use Rutatiina\Expense\Traits\Recurring\AccountBalanceUpdate as TxnTraitsAccountBalanceUpdate;
use Rutatiina\Expense\Traits\Recurring\ContactBalanceUpdate as TxnTraitsContactBalanceUpdate;
use Rutatiina\Expense\Traits\Recurring\Approve as TxnTraitsApprove;

class Store
{
    use TxnTraitsInit;
    use TxnTraitsInventory;
    use TxnTraitsTxnItemsContactsIdsLedgers;
    use TxnTraitsTxnItemsJournalLedgers;
    use TxnTraitsTxnTypeBasedSpecifics;
    use TxnTraitsValidate;
    use TxnTraitsAccountBalanceUpdate;
    use TxnTraitsContactBalanceUpdate;
    use TxnTraitsApprove;

    public function __construct()
    {
    }

    public function run()
    {
        //print_r($this->txnInsertData); exit;

        $verifyWebData = $this->validate();
        if ($verifyWebData === false) return false;

        //check if inventory is affected and if its available
        $inventoryAvailability = $this->inventoryAvailability();
        if ($inventoryAvailability === false) return false;

        //Log::info($this->txn);
        //var_dump($this->txn); exit;
        //print_r($this->txn); exit;
        //echo json_encode($this->txn); exit;

        //print_r($this->txn); exit; //$this->txn, $this->txn['items'], $this->txn[number], $this->txn[ledgers], $this->txn[recurring]

        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            //print_r($this->txn); exit;
            $Txn = new ExpenseRecurring;
            $Txn->tenant_id = $this->txn['tenant_id'];
            $Txn->document_name = $this->txn['document_name'];
            $Txn->number_prefix = $this->txn['number_prefix'];
            $Txn->number = $this->txn['number'];
            $Txn->number_length = $this->txn['number_length'];
            $Txn->number_postfix = $this->txn['number_postfix'];
            $Txn->date = $this->txn['date'];
            $Txn->debit_financial_account_code = $this->txn['debit_financial_account_code'];
            $Txn->credit_financial_account_code = $this->txn['credit_financial_account_code'];
            $Txn->debit_contact_id = $this->txn['debit_contact_id'];
            $Txn->credit_contact_id = $this->txn['credit_contact_id'];
            $Txn->contact_name = $this->txn['contact_name'];
            $Txn->contact_address = $this->txn['contact_address'];
            $Txn->reference = $this->txn['reference'];
            $Txn->base_currency = $this->txn['base_currency'];
            $Txn->quote_currency = $this->txn['quote_currency'];
            $Txn->exchange_rate = $this->txn['exchange_rate'];
            $Txn->taxable_amount = $this->txn['taxable_amount'];
            $Txn->total = $this->txn['total'];
            $Txn->balance = $this->txn['balance'];
            $Txn->branch_id = $this->txn['branch_id'];
            $Txn->store_id = $this->txn['store_id'];
            $Txn->due_date = $this->txn['due_date'];
            $Txn->expiry_date = $this->txn['expiry_date'];
            $Txn->terms_and_conditions = $this->txn['terms_and_conditions'];
            $Txn->external_ref = $this->txn['external_ref'];
            $Txn->payment_mode = $this->txn['payment_mode'];
            $Txn->payment_terms = $this->txn['payment_terms'];
            $Txn->status = $this->txn['status'];

            $Txn->save();
            $this->txn['id'] = $Txn->id;

            //Save the items >> $this->txn['items']
            foreach ($this->txn['items'] as &$item)
            {
                $item['expense_recurring_id'] = $this->txn['id'];

                $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
                unset($item['taxes']);

                $itemModel = ExpenseRecurringItem::create($item);

                foreach ($itemTaxes as $tax)
                {
                    //save the taxes attached to the item
                    $itemTax = new ExpenseRecurringItemTax;
                    $itemTax->tenant_id = $item['tenant_id'];
                    $itemTax->expense_recurring_id = $item['expense_recurring_id'];
                    $itemTax->expense_recurring_item_id = $itemModel->id;
                    $itemTax->tax_code = $tax['code'];
                    $itemTax->amount = $tax['total'];
                    $itemTax->inclusive = $tax['inclusive'];
                    $itemTax->exclusive = $tax['exclusive'];
                    $itemTax->save();
                }
                unset($tax);
            }

            unset($item);

            //Save the ledgers >> $this->txn['ledgers']; and update the balances
            foreach ($this->txn['ledgers'] as &$ledger)
            {
                $ledger['expense_recurring_id'] = $this->txn['id'];
                ExpenseRecurringLedger::create($ledger);
            }
            unset($ledger);

            //save the recurring properties
            $TxnRecurring = new ExpenseRecurringProperty;
            $TxnRecurring->tenant_id = $this->txn['tenant_id'];
            $TxnRecurring->expense_recurring_id = $this->txn['id'];
            $TxnRecurring->status = $this->txn['recurring']['status'];
            $TxnRecurring->frequency = $this->txn['recurring']['frequency'];
            //$TxnRecurring->measurement = $this->txn['recurring']['frequency']; //of no use
            $TxnRecurring->start_date = $this->txn['recurring']['start_date'];
            $TxnRecurring->end_date = $this->txn['recurring']['end_date'];
            $TxnRecurring->day_of_month = $this->txn['recurring']['day_of_month'];
            $TxnRecurring->month = $this->txn['recurring']['month'];
            $TxnRecurring->day_of_week = $this->txn['recurring']['day_of_week'];
            $TxnRecurring->save();

            $this->approve();

            DB::connection('tenant')->commit();

            return (object)[
                'id' => $this->txn['id'],
            ];

        }
        catch (\Exception $e)
        {
            DB::connection('tenant')->rollBack();
            //print_r($e); exit;
            if (App::environment('local'))
            {
                $this->errors[] = 'Error: Failed to save transaction to database.';
                $this->errors[] = 'File: ' . $e->getFile();
                $this->errors[] = 'Line: ' . $e->getLine();
                $this->errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                $this->errors[] = 'Fatal Internal Error: Failed to save transaction to database. Please contact Admin';
            }

            return false;
        }

    }

}
