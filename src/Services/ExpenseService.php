<?php

namespace Rutatiina\Expense\Services;

use Rutatiina\Tax\Models\Tax;
use Rutatiina\Item\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Rutatiina\Expense\Models\Expense;
use Rutatiina\Expense\Models\ExpenseLedger;
use Rutatiina\Expense\Models\ExpenseSetting;
use Rutatiina\FinancialAccounting\Models\Account;
use Rutatiina\FinancialAccounting\Services\AccountBalanceUpdateService;
use Rutatiina\FinancialAccounting\Services\ContactBalanceUpdateService;

class ExpenseService
{
    public static $errors = [];

    public function __construct()
    {
        //
    }

    public static function nextNumber()
    {
        $count = Expense::count();
        $settings = ExpenseSetting::first();

        return $settings->number_prefix . (str_pad(($count + 1), $settings->minimum_number_length, "0", STR_PAD_LEFT)) . $settings->number_postfix;
    }

    public static function edit($id)
    {
        $taxes = Tax::all()->keyBy('code');

        $txn = Expense::findOrFail($id);
        $txn->load('contact', 'items.taxes');
        $txn->setAppends(['taxes']);

        $attributes = $txn->toArray();

        //print_r($attributes); exit;

        $attributes['_method'] = 'PATCH';

        $attributes['contact']['currency'] = $txn->contact->currency_and_exchange_rate;
        $attributes['contact']['currencies'] = $txn->contact->currencies_and_exchange_rates;

        $attributes['taxes'] = json_decode('{}');

        foreach ($attributes['items'] as &$item)
        {
            $selectedItem = [
                'id' => 0,
                'description' => $item['description'],
                'rate' => $item['amount'],
                'tax_method' => 'inclusive',
                'account_type' => null,
            ];

            $item['selectedItem'] = $selectedItem; #required
            $item['selectedTaxes'] = []; #required
            $item['displayTotal'] = 0; #required

            foreach ($item['taxes'] as $itemTax)
            {
                $item['selectedTaxes'][] = $taxes[$itemTax['tax_code']];
            }

            $item['amount'] = floatval($item['amount']);
            $item['taxable_amount'] = floatval($item['amount']);
            $item['displayTotal'] = $item['amount']; #required
        };

        $attributes['total'] = floatval($attributes['total']); #required

        return $attributes;
    }

    public static function store($requestInstance)
    {
        $data = ExpenseValidateService::run($requestInstance);
        //print_r($data); exit;
        if ($data === false)
        {
            self::$errors = ExpenseValidateService::$errors;
            return false;
        }

        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            $Txn = new Expense;
            $Txn->tenant_id = $data['tenant_id'];
            $Txn->created_by = Auth::id();
            $Txn->document_name = $data['document_name'];
            $Txn->number = $data['number'];
            $Txn->date = $data['date'];
            // $Txn->debit_financial_account_code = $data['debit_financial_account_code']; //this is being deprecated on 1st Nov 20222 and move to the items table
            $Txn->credit_financial_account_code = $data['credit_financial_account_code'];
            $Txn->contact_id = $data['contact_id'];
            $Txn->contact_name = $data['contact_name'];
            $Txn->contact_address = $data['contact_address'];
            $Txn->reference = $data['reference'];
            $Txn->base_currency = $data['base_currency'];
            $Txn->quote_currency = $data['quote_currency'];
            $Txn->exchange_rate = $data['exchange_rate'];
            $Txn->taxable_amount = $data['taxable_amount'];
            $Txn->total = $data['total'];
            $Txn->payment_mode = $data['payment_mode'];
            $Txn->branch_id = $data['branch_id'];
            $Txn->store_id = $data['store_id'];
            $Txn->contact_notes = $data['contact_notes'];
            $Txn->terms_and_conditions = $data['terms_and_conditions'];
            $Txn->status = $data['status'];

            $Txn->save();

            $data['id'] = $Txn->id;

            //print_r($data['items']); exit;

            //Save the items >> $data['items']
            ExpenseItemService::store($data);

            //Save the ledgers >> $data['ledgers']; and update the balances
            $Txn->ledgers()->createMany($data['ledgers']);

            //$Txn->refresh(); //make the ledgers relationship infor available

            //update financial account and contact balances accordingly
            $Txn = $Txn->fresh(['items', 'ledgers']);
            ExpenseApprovalService::run($Txn);

            DB::connection('tenant')->commit();

            return $Txn;

        }
        catch (\Throwable $e)
        {
            DB::connection('tenant')->rollBack();

            Log::critical('Fatal Internal Error: Failed to save expense to database');
            Log::critical($e);

            //print_r($e); exit;
            if (App::environment('local'))
            {
                self::$errors[] = 'Error: Failed to save expense to database.';
                self::$errors[] = 'File: ' . $e->getFile();
                self::$errors[] = 'Line: ' . $e->getLine();
                self::$errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                self::$errors[] = 'Fatal Internal Error: Failed to save expense to database. Please contact Admin';
            }

            return false;
        }
        //*/

    }

    public static function update($requestInstance)
    {
        $data = ExpenseValidateService::run($requestInstance);
        //print_r($data); exit;
        if ($data === false)
        {
            self::$errors = ExpenseValidateService::$errors;
            return false;
        }

        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            $Txn = Expense::with('items', 'ledgers')->findOrFail($data['id']);

            if ($Txn->status == 'approved')
            {
                self::$errors[] = 'Approved expense cannot be not be edited';
                return false;
            }

            //reverse the account balances
            AccountBalanceUpdateService::doubleEntry($Txn->toArray(), true);

            //reverse the contact balances
            ContactBalanceUpdateService::doubleEntry($Txn->toArray(), true);

            //Delete affected relations
            $Txn->ledgers()->delete();
            $Txn->items()->delete();
            $Txn->item_taxes()->delete();
            $Txn->comments()->delete();
            $Txn->delete();

            $txnStore = self::store($requestInstance);

            DB::connection('tenant')->commit();

            return $txnStore;

        }
        catch (\Throwable $e)
        {
            DB::connection('tenant')->rollBack();

            Log::critical('Fatal Internal Error: Failed to update estimate in database');
            Log::critical($e);

            //print_r($e); exit;
            if (App::environment('local'))
            {
                self::$errors[] = 'Error: Failed to update estimate in database.';
                self::$errors[] = 'File: ' . $e->getFile();
                self::$errors[] = 'Line: ' . $e->getLine();
                self::$errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                self::$errors[] = 'Fatal Internal Error: Failed to update estimate in database. Please contact Admin';
            }

            return false;
        }

    }

    public static function destroy($id)
    {
        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            $Txn = Expense::with('items', 'ledgers')->findOrFail($id);

            if ($Txn->status == 'approved')
            {
                self::$errors[] = 'Approved expenses(s) cannot be not be deleted';
                return false;
            }

            //reverse the account balances
            AccountBalanceUpdateService::doubleEntry($Txn, true);

            //reverse the contact balances
            ContactBalanceUpdateService::doubleEntry($Txn, true);

            //Delete affected relations
            $Txn->ledgers()->delete();
            $Txn->items()->delete();
            $Txn->item_taxes()->delete();
            $Txn->comments()->delete();
            $Txn->delete();

            DB::connection('tenant')->commit();

            return true;

        }
        catch (\Throwable $e)
        {
            DB::connection('tenant')->rollBack();

            Log::critical('Fatal Internal Error: Failed to delete estimate from database');
            Log::critical($e);

            //print_r($e); exit;
            if (App::environment('local'))
            {
                self::$errors[] = 'Error: Failed to delete estimate from database.';
                self::$errors[] = 'File: ' . $e->getFile();
                self::$errors[] = 'Line: ' . $e->getLine();
                self::$errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                self::$errors[] = 'Fatal Internal Error: Failed to delete estimate from database. Please contact Admin';
            }

            return false;
        }
    }

    public static function approve($id)
    {
        $Txn = Expense::with(['items', 'ledgers'])->findOrFail($id);

        if (strtolower($Txn->status) != 'draft')
        {
            self::$errors[] = $Txn->status . ' transaction cannot be approved';
            return false;
        }

        //start database transaction
        DB::connection('tenant')->beginTransaction();

        try
        {
            $Txn->status = 'approved';
            ExpenseApprovalService::run($Txn);

            DB::connection('tenant')->commit();

            return true;

        }
        catch (\Exception $e)
        {
            DB::connection('tenant')->rollBack();
            //print_r($e); exit;
            if (App::environment('local'))
            {
                self::$errors[] = 'DB Error: Failed to approve expense.';
                self::$errors[] = 'File: ' . $e->getFile();
                self::$errors[] = 'Line: ' . $e->getLine();
                self::$errors[] = 'Message: ' . $e->getMessage();
            }
            else
            {
                self::$errors[] = 'Fatal Internal Error: Failed to approve expense.';
            }

            return false;
        }
    }
    
    public static function inventoryItems($txnArrayOrModel)
    {
        $inventoryItems = [];

        // print_r($txnArrayOrModel); exit;

        foreach ($txnArrayOrModel['items'] as $key => $item)
        {
            if (!Item::find($item['item_id'])) continue; //skip the item if the item_id is not found

            $_item_ = (is_array($item)) ? $item : $item->toArray();
            //inventory Items
            $financialAccountToDebitModel = Account::findCode($item['debit_financial_account_code']);
            // print_r($financialAccountToDebitModel); exit;
            if ($financialAccountToDebitModel->type == 'asset' && $financialAccountToDebitModel->sub_type == 'inventory')
            {
                $_item_['financial_account_code'] = $_item_['debit_financial_account_code'];
                $_item_['batch'] = '';
                $_item_['units'] = $_item_['quantity'];

                $inventoryItems[] = $_item_;
            }
        }
        
        return $inventoryItems;
    }

}
