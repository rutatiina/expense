<?php

namespace Rutatiina\Expense\Services;

use Rutatiina\GoodsReceived\Services\GoodsReceivedInventoryService;
use Rutatiina\FinancialAccounting\Services\AccountBalanceUpdateService;
use Rutatiina\FinancialAccounting\Services\ContactBalanceUpdateService;

trait ExpenseApprovalService
{
    public static function run($txn)
    {
        if (strtolower($txn['status']) == 'draft')
        {
            //cannot update balances for drafts
            return false;
        }

        if (isset($txn['balances_where_updated']) && $txn['balances_where_updated'])
        {
            //cannot update balances for task already completed
            return false;
        }

        //inventory checks and inventory balance update if needed
        //$this->inventory(); //currently inventory update for estimates is disabled

        //Update the account balances
        AccountBalanceUpdateService::doubleEntry($txn);

        //Update the contact balances
        ContactBalanceUpdateService::doubleEntry($txn);

        //update inventory if an item in bill has cost account as inventory account
        //items that have to be added to the inventory
        $data = $txn->toArray(); //to prevent error in saving txn status
        $data['inventory_items'] = ExpenseService::inventoryItems($data);
        // print_r($data['inventory_items']); exit;

        //Update the inventory if any item belong is DR'ed to an inventory 'sub_type'
        GoodsReceivedInventoryService::update($data);

        $txn->status = 'approved';
        $txn->balances_where_updated = 1;
        $txn->save();

        return true;
    }

}
