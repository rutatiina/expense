<?php

namespace Rutatiina\Expense\Http\Controllers;

use Rutatiina\Expense\Models\Setting;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Rutatiina\Expense\Models\Expense;
use Rutatiina\Expense\Services\ExpenseService;
use Rutatiina\FinancialAccounting\Traits\FinancialAccountingTrait;
use Rutatiina\Contact\Traits\ContactTrait;
use Yajra\DataTables\Facades\DataTables;

use Rutatiina\Expense\Classes\Store as TxnStore;
use Rutatiina\Expense\Classes\Approve as TxnApprove;
use Rutatiina\Expense\Classes\Read as TxnRead;
use Rutatiina\Expense\Classes\Copy as TxnCopy;
use Rutatiina\Expense\Classes\Number as TxnNumber;
use Rutatiina\Expense\Traits\Item as TxnItem;

class ExpenseController extends Controller
{
    use FinancialAccountingTrait;
    use ContactTrait;
    use TxnItem;

    // >> get the item attributes template << !!important

    public function __construct()
    {
        $this->middleware('permission:expenses.view');
        $this->middleware('permission:expenses.create', ['only' => ['create', 'store']]);
        $this->middleware('permission:expenses.update', ['only' => ['edit', 'update']]);
        $this->middleware('permission:expenses.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $query = Expense::query();

        if ($request->contact)
        {
            $query->where(function ($q) use ($request)
            {
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

    public function create()
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $tenant = Auth::user()->tenant;

        $txnAttributes = (new Expense())->rgGetAttributes();

        $txnAttributes['number'] = ExpenseService::nextNumber();
        $txnAttributes['status'] = 'approved';
        $txnAttributes['contact_id'] = '';
        $txnAttributes['contact'] = json_decode('{"currencies":[]}'); #required
        $txnAttributes['date'] = date('Y-m-d');
        $txnAttributes['base_currency'] = $tenant->base_currency;
        $txnAttributes['quote_currency'] = $tenant->base_currency;
        $txnAttributes['taxes'] = json_decode('{}');
        $txnAttributes['contact_notes'] = null;
        $txnAttributes['terms_and_conditions'] = null;
        $txnAttributes['items'] = [
            [
                'selectedTaxes' => [], #required
                'selectedItem' => json_decode('{}'), #required
                'displayTotal' => 0,
                'description' => '',
                'amount' => 0,
                'contact_id' => '',
            ]
        ];

        unset($txnAttributes['debit_contact_id']); //!important
        unset($txnAttributes['credit_contact_id']); //!important

        $data = [
            'pageTitle' => 'Record Expense', #required
            'pageAction' => 'Record', #required
            'txnUrlStore' => '/expenses', #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;

    }

    public function store(Request $request)
    {
        $storeService = ExpenseService::store($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => ExpenseService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Expense saved'],
            'number' => 0,
            'callback' => URL::route('expenses.show', [$storeService->id], false)
        ];

    }

    public function show($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txn = Expense::findOrFail($id);
        $txn->load('contact', 'items.taxes');
        $txn->setAppends([
            'taxes',
            'number_string',
            'total_in_words',
        ]);

        return $txn->toArray();
    }

    public function edit($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
            return view('l-limitless-bs4.layout_2-ltr-default.appVue');
        }

        $txnAttributes = ExpenseService::edit($id);

        $data = [
            'pageTitle' => 'Edit expense', #required
            'pageAction' => 'Edit', #required
            'txnUrlStore' => '/expenses/' . $id, #required
            'txnAttributes' => $txnAttributes, #required
        ];

        return $data;
    }

    public function update(Request $request)
    {
        //editing an expense is not currently allowed
        //return redirect()->back();

        $storeService = ExpenseService::update($request);

        if ($storeService == false)
        {
            return [
                'status' => false,
                'messages' => ExpenseService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Expense updated'],
            'number' => 0,
            'callback' => URL::route('expenses.show', [$storeService->id], false)
        ];
    }

    public function destroy($id)
    {
        $destroy = ExpenseService::destroy($id);

        if ($destroy)
        {
            return [
                'status' => true,
                'messages' => ['Expense deleted'],
                'callback' => URL::route('expenses.index', [], false)
            ];
        }
        else
        {
            return [
                'status' => false,
                'messages' => ExpenseService::$errors
            ];
        }
    }

    #-----------------------------------------------------------------------------------

    public function approve($id)
    {
        $approve = ExpenseService::approve($id);

        if ($approve == false)
        {
            return [
                'status' => false,
                'messages' => ExpenseService::$errors
            ];
        }

        return [
            'status' => true,
            'messages' => ['Expenses approved'],
        ];

    }

    public function copy($id)
    {
        //load the vue version of the app
        if (!FacadesRequest::wantsJson())
        {
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

        if (FacadesRequest::wantsJson())
        {
            return $data;
        }
    }

    public function datatables(Request $request)
    {

        $txns = Transaction::setRoute('show', route('accounting.purchases.expenses.show', '_id_'))
            ->setRoute('copy', route('accounting.purchases.expenses.copy', '_id_'))
            ->setRoute('edit', route('accounting.purchases.expenses.edit', '_id_'))
            ->setSortBy($request->sort_by)
            ->paginate(false)
            ->findByEntree($this->txnEntreeSlug);

        return Datatables::of($txns)->make(true);
    }

    public function exportToExcel(Request $request)
    {

        $txns = collect([]);

        $txns->push([
            'DATE',
            'EXPENSE ACCOUNT',
            'REFERENCE',
            'SUPPLIER / VENDOR',
            'PAID THROUGH',
            'CUSTOMER NAME',
            'AMOUNT',
            ' ', //Currency
        ]);

        foreach (array_reverse($request->ids) as $id)
        {
            $txn = Transaction::transaction($id);

            $txns->push([
                $txn->date,
                $txn->debit_account->name,
                $txn->reference,
                $txn->contact_name,
                $txn->credit_account->name,
                '',
                $txn->total,
                $txn->base_currency,
            ]);
        }

        $export = $txns->downloadExcel(
            'maccounts-expenses-export-' . date('Y-m-d-H-m-s') . '.xlsx',
            null,
            false
        );

        //$books->load('author', 'publisher'); //of no use

        return $export;
    }
}