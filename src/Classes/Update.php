<?php

namespace Rutatiina\Expense\Classes;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Rutatiina\Expense\Models\ExpenseItem;
use Rutatiina\Expense\Models\ExpenseItemTax;
use Rutatiina\Expense\Models\ExpenseLedger;
use Rutatiina\FinancialAccounting\Models\Txn;
use Rutatiina\FinancialAccounting\Models\TxnItem;
use Rutatiina\FinancialAccounting\Models\TxnType;
use Rutatiina\FinancialAccounting\Models\TxnNumber;
use Rutatiina\FinancialAccounting\Models\TxnLedger;

use Rutatiina\Expense\Traits\Init as TxnTraitsInit;
use Rutatiina\Expense\Traits\Inventory as TxnTraitsInventory;
use Rutatiina\Expense\Traits\InventoryReverse as TxnTraitsInventoryReverse;
use Rutatiina\Expense\Traits\TxnItemsContactsIdsLedgers as TxnTraitsTxnItemsContactsIdsLedgers;
use Rutatiina\Expense\Traits\TxnTypeBasedSpecifics as TxnTraitsTxnTypeBasedSpecifics;
use Rutatiina\Expense\Traits\Validate as TxnTraitsValidate;
use Rutatiina\Expense\Traits\AccountBalanceUpdate as TxnTraitsAccountBalanceUpdate;
use Rutatiina\Expense\Traits\ContactBalanceUpdate as TxnTraitsContactBalanceUpdate;
use Rutatiina\Expense\Traits\Approve as TxnTraitsApprove;

class Update
{
    use TxnTraitsInit;
    use TxnTraitsInventory;
    use TxnTraitsInventoryReverse;
    use TxnTraitsTxnItemsContactsIdsLedgers;
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

        $Txn = Txn::with('items', 'ledgers', 'debit_account', 'credit_account')->find($this->txn['id']);

        if (!$Txn)
        {
            $this->errors[] = 'Transaction not found';
            return false;
        }

        if ($Txn->status == 'Approved')
        {
            $this->errors[] = 'Approved Transaction cannot be not be edited';
            return false;
        }

        $this->txn['original'] = $Txn->toArray();

        //check if inventory is affected and if its available
        $inventoryAvailability = $this->inventoryAvailability();
        if ($inventoryAvailability === false) return false;

        //Log::info($this->txn);
        //var_dump($this->txn); exit;
        //print_r($this->txn); exit;
        //echo json_encode($this->txn); exit;


        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {

            //Delete affected relations
            $Txn->ledgers()->delete();
            $Txn->items()->delete();
            $Txn->item_taxes()->delete();

            // >> reverse all the inventory and balance effects
            //inventory checks and inventory balance update if needed
            $this->inventoryReverse();

            //Update the account balances
            $this->accountBalanceUpdate(true);

            //Update the contact balances
            $this->contactBalanceUpdate(true);
            // << reverse all the inventory and balance effects

            $txnId = $Txn->id;

            //print_r($this->txn); exit; //$this->txn, $this->txn['items'], $this->txn[number], $this->txn[ledgers], $this->txn[recurring]

            //print_r($this->txn); exit;
            $Txn->user_id = $this->txn['user_id'];
            $Txn->app = $this->txn['app'];
            $Txn->app_id = $this->txn['app_id'];
            $Txn->created_by = $this->txn['created_by'];
            $Txn->internal_ref = $this->txn['internal_ref'];
            $Txn->txn_entree_id = $this->txn['entree']['id'];
            $Txn->txn_type_id = $this->txn['type']['id'];
            $Txn->number = $this->txn['number'];
            $Txn->date = $this->txn['date'];
            $Txn->debit_financial_account_code = $this->txn['debit_financial_account_code'];
            $Txn->credit_financial_account_code = $this->txn['credit_financial_account_code'];
            $Txn->debit_contact_id = $this->txn['debit_contact_id'];
            $Txn->credit_contact_id = $this->txn['credit_contact_id'];
            $Txn->contact_name = $this->txn['contact_name'];
            $Txn->contact_address = $this->txn['contact_address'];
            $Txn->reference = $this->txn['reference'];
            $Txn->invoice_number = $this->txn['invoice_number'];
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


            foreach ($this->txn['items'] as &$item)
            {
                $item['txn_id'] = $txnId;

                $itemTaxes = (is_array($item['taxes'])) ? $item['taxes'] : [] ;
                unset($item['taxes']);

                $itemModel = ExpenseItem::create($item);

                foreach ($itemTaxes as $tax)
                {
                    //save the taxes attached to the item
                    $itemTax = new ExpenseItemTax;
                    $itemTax->tenant_id = $item['tenant_id'];
                    $itemTax->expense_id = $item['expense_id'];
                    $itemTax->expense_item_id = $itemModel->id;
                    $itemTax->tax_code = $tax['code'];
                    $itemTax->amount = $tax['total'];
                    $itemTax->inclusive = $tax['inclusive'];
                    $itemTax->exclusive = $tax['exclusive'];
                    $itemTax->save();
                }
                unset($tax);
            }
            unset($item);


            //print_r($this->txn['items']); exit;

            foreach ($this->txn['ledgers'] as &$ledger)
            {
                $ledger['txn_id'] = $txnId;
                ExpenseLedger::create($ledger);
            }
            unset($ledger);


            $this->approve();


            DB::connection('tenant')->commit();

            return (object)[
                'id' => $txnId,
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
