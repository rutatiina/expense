<?php

namespace Rutatiina\Expense\Http\Controllers;

use Rutatiina\Expense\Models\ExpenseRecurringSetting;
use Rutatiina\FinancialAccounting\Models\Entree;
use URL;
use PDF;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Illuminate\Support\Facades\View;
use Rutatiina\Expense\Models\ExpenseRecurring;
use Rutatiina\FinancialAccounting\Classes\Transaction;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;
use Rutatiina\Contact\Traits\ContactTrait;
use Yajra\DataTables\Facades\DataTables;

use Rutatiina\Expense\Classes\Recurring\Store as TxnStore;
use Rutatiina\Expense\Classes\Recurring\Approve as TxnApprove;
use Rutatiina\Expense\Classes\Recurring\Read as TxnRead;
use Rutatiina\Expense\Classes\Recurring\Copy as TxnCopy;
use Rutatiina\Expense\Classes\Recurring\Number as TxnNumber;
use Rutatiina\Expense\Traits\Recurring\Item as TxnItem;

class RecurringController extends Controller
{
    use FinancialAccountingTrait;
    use ContactTrait;
    use TxnItem; // >> get the item attributes template << !!important

    public function __construct()
    {
        $this->middleware('permission:recurring-expenses.view');
		$this->middleware('permission:recurring-expenses.create', ['only' => ['create','store']]);
		$this->middleware('permission:recurring-expenses.update', ['only' => ['edit','update']]);
		$this->middleware('permission:recurring-expenses.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $query = ExpenseRecurring::query();

        if ($request->contact)
        {
            $query->where(function($q) use ($request) {
                $q->where('debit_contact_id', $request->contact);
                $q->orWhere('credit_contact_id', $request->contact);
            });
        }

        $txns = $query->latest()->paginate($request->input('per_page', 20));

        $txns->load('debit_account', 'credit_account');

        return [
            'tableData' => $txns
        ];
    }

    private function nextNumber()
    {
        $txn = ExpenseRecurring::latest()->first();
        $settings = ExpenseRecurringSetting::first();

        return $settings->number_prefix.(str_pad((optional($txn)->number+1), $settings->minimum_number_length, "0", STR_PAD_LEFT)).$settings->number_postfix;
    }

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new ExpenseRecurring())->rgGetAttributes();

        $txnAttributes['number'] = $this->nextNumber();
        $txnAttributes['status'] = 'Approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['isRecurring'] = true;
        $txnAttributes['recurring'] = [
            'status' => 'active',
            'frequency' => 'monthly',
            'date_range' => [], //used by vue
            'start_date' => '',
            'end_date' => '',
            'day_of_month' => '*',
            'month' => '*',
            'day_of_week' => '*',
        ];
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [$this->itemCreate()];

        unset($txnAttributes['txn_entree_id']); //!important
        unset($txnAttributes['txn_type_id']); //!important
        unset($txnAttributes['debit_contact_id']); //!important
        unset($txnAttributes['credit_contact_id']); //!important

        $data = [
            'pageTitle' => 'Create Recurring Expense', #required
            'pageAction' => 'Create', #required
            'txnUrlStore' => '/recurring-expenses', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;

    }

    public function store(Request $request)
	{
        $TxnStore = new TxnStore();
        $TxnStore->txnInsertData = $request->all();
        $insert = $TxnStore->run();

        if ($insert == false) {
            return [
                'status'    => false,
                'messages'   => $TxnStore->errors
            ];
        }

        return [
            'status'    => true,
            'messages'   => ['Recurring Expense saved'],
            'number'    => 0,
            'callback'  => URL::route('recurring-expenses.show', [$insert->id], false)
        ];

    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        if (FacadesRequest::wantsJson()) {
            $TxnRead = new TxnRead();
            return $TxnRead->run($id);
        }
    }

    public function edit($id)
	{
        $txn = Transaction::transaction($id);
        return view('accounting::purchases.recurring-expenses.edit')->with([
            'txn' => $txn,
            'contacts' => static::contactsByTypes(['supplier']),
            'accounts' => self::accounts(),
            'taxes' => self::taxes()
        ]);
    }

    public function update(Request $request)
	{
        $data = $request->all();

        //$data['txn_entree_id']  = $this->entree->id; //New Tax invoice txn_entree
        $data['txn_entree_name']    = 'recurring_expense';

        //print_r($data); exit;

        $process = Transaction::contactById($request->contact_id)->update($request->id, $data);

        if ($process == false) {
            return [
                'status'    => false,
                'message'   => implode('<br>', array_values(Transaction::$rg_errors))
            ];
        }

        return [
            'status'    => true,
            'message'   => 'Recurring Expense updated',
            'callback'  => route('recurring-expenses.show', $request->id)
        ];
    }

    public function destroy()
	{}

	#-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $TxnApprove = new TxnApprove();
        $approve = $TxnApprove->run($id);

        if ($approve == false) {
            return [
                'status'    => false,
                'messages'   => $TxnApprove->errors
            ];
        }

        return [
            'status'    => true,
            'messages'   => ['Recurring Expense Approved'],
        ];

    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson()) {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $TxnCopy = new TxnCopy();
        $txnAttributes = $TxnCopy->run($id);

        $TxnNumber = new TxnNumber();
        $txnAttributes['number'] = $TxnNumber->run($this->txnEntreeSlug);

        $data = [
            'pageTitle' => 'Copy Receipts', #required
            'pageAction' => 'Copy', #required
            'txnUrlStore' => '/payments', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        if (FacadesRequest::wantsJson()) {
            return $data;
        }
    }

    public function datatables(Request $request)
	{
        $txns = Transaction::setRoute('show', route('accounting.purchases.recurring-expenses.show', '_id_'))
			->setRoute('copy', route('accounting.purchases.recurring-expenses.copy', '_id_'))
			->setRoute('edit', route('accounting.purchases.recurring-expenses.edit', '_id_'))
			->setSortBy($request->sort_by)
			->paginate(false)
			->findByEntree($this->txnEntreeSlug);

        return Datatables::of($txns)->make(true);
    }
}
