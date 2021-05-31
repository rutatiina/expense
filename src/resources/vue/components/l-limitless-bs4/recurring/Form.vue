<template>

    <!-- Main content -->
    <div class="content-wrapper">

        <!-- Page header -->
        <div class="page-header page-header-light">
            <div class="page-header-content header-elements-md-inline">
                <div class="page-title d-flex">
                    <h4>
                        <i class="icon-file-plus"></i>
                        {{pageTitle}}
                    </h4>
                </div>

            </div>

            <div class="breadcrumb-line breadcrumb-line-light header-elements-md-inline">
                <div class="d-flex">
                    <div class="breadcrumb">
                        <a href="/" class="breadcrumb-item">
                            <i class="icon-home2 mr-2"></i>
                            <span class="badge badge-primary badge-pill font-weight-bold rg-breadcrumb-item-tenant-name"> {{this.$root.tenant.name | truncate(30) }} </span>
                        </a>
                        <span class="breadcrumb-item">Accounting</span>
                        <span class="breadcrumb-item">Purchases</span>
                        <span class="breadcrumb-item">Recurring Expenses</span>
                        <span class="breadcrumb-item active">{{pageAction}}</span>
                    </div>

                    <a href="#" class="header-elements-toggle text-default d-md-none"><i class="icon-more"></i></a>
                </div>

                <div class="header-elements">
                    <div class="breadcrumb justify-content-center">
                        <router-link :to="txnUrlStore" class=" btn btn-danger btn-sm rounded-round font-weight-bold">
                            <i class="icon-drawer3 mr-2"></i>
                            Recurring Expenses
                        </router-link>
                    </div>
                </div>

            </div>

        </div>
        <!-- /page header -->

        <!-- Content area -->
        <div class="content border-0 padding-0">

            <!-- Form horizontal -->
            <div class="card shadow-none rounded-0 border-0">

                <div class="card-body p-0">

                    <loading-animation></loading-animation>

                    <form v-if="!this.$root.loading"
                          @submit="txnFormSubmit"
                          action=""
                          method="post"
                          class="max-width-1040"
                          style="margin-bottom: 100px;"
                          autocomplete="off">

                        <fieldset class="">

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">
                                    Expense Account:
                                </label>
                                <div class="col-lg-5">
                                    <model-list-select :list="txnAccountsExpenses"
                                                       v-model="txnAttributes.items[0].type_id"
                                                       option-value="code"
                                                       option-text="name"
                                                       @input="expenseAccountInput"
                                                       placeholder="select account">
                                    </model-list-select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label font-weight-bold">
                                    Date & Reference:
                                </label>
                                <div class="col-lg-2" title="Invoice date">
                                    <date-picker v-model="txnAttributes.date"
                                                 valueType="format"
                                                 :lang="vue2DatePicker.lang"
                                                 class="font-weight-bold w-100 h-100"
                                                 placeholder="Invoice date">
                                    </date-picker>
                                </div>
                                <div class="col-lg-3">
                                    <input type="text" name="reference" v-model="txnAttributes.reference" class="form-control input-roundless" placeholder="Enter reference">
                                </div>
                            </div>

                        </fieldset>

                        <fieldset id="fieldset_select_contact" class="select_contact_required">

                            <div class="form-group row">
                                <label class="col-form-label col-lg-2 text-danger font-weight-bold">Supplier / Vendor</label>
                                <div class="col-lg-6">
                                    <model-list-select :list="txnContacts"
                                                       v-model="txnAttributes.contact"
                                                       @searchchange="txnFetchSuppliers"
                                                       @input="txnContactSelect"
                                                       option-value="id"
                                                       option-text="display_name"
                                                       placeholder="select contact">
                                    </model-list-select>
                                </div>

                                <div v-show="txnAttributes.contact_id" class="col-lg-1 p-0" >
                                    <model-list-select :list="txnAttributes.contact.currencies"
                                                       v-model="txnAttributes.contact.currency"
                                                       option-value="code"
                                                       option-text="code"
                                                       placeholder="select currency">
                                    </model-list-select>
                                </div>

                                <div class="col-lg-2 pr-0"
                                     v-show="txnAttributes.contact_id && txnAttributes.base_currency !== txnAttributes.quote_currency">
                                    <div class="input-group" title="Exchange rate">
											<span class="input-group-prepend">
												<span class="input-group-text">XR</span>
											</span>
                                        <input type="text"
                                               v-model="txnAttributes.exchange_rate"
                                               class="form-control text-right"
                                               placeholder="Exchange rate">
                                    </div>
                                </div>

                            </div>

                        </fieldset>

                        <fieldset>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">
                                    Amount paid
                                </label>
                                <div class="col-lg-3">
                                    <div class="input-group">
                                        <span class="input-group-prepend">
                                            <span class="input-group-text font-weight-bold">{{txnAttributes.base_currency}}</span>
                                        </span>
                                        <input type="text"
                                               v-model.number="txnAttributes.items[0].rate"
                                               v-on:keyup="txnTotal"
                                               class="form-control font-weight-semibold text-right"
                                               placeholder="Amount">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="row">
                                        <label class="col-auto col-form-label text-right bg-light border rounded-left border-right-0"
                                               style="white-space: nowrap;">
                                            Tax:
                                        </label>
                                        <div class="col pl-0">
                                            <multi-list-select
                                                :list="txnTaxes"
                                                option-value="id"
                                                option-text="display_name"
                                                :option-item-row="0"
                                                class="rounded-left-0"
                                                :selected-items="txnAttributes.items[0].selectedTaxes"
                                                placeholder="select tax"
                                                show-count-of-selected-options
                                                @select="txnItemTaxes">
                                            </multi-list-select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row" v-for="tax in txnAttributes.taxes">
                                <label class="col-lg-2 col-form-label"> </label>
                                <div class="col-lg-3 pl-4 border-left-2 border-left-indigo-400 rounded-0">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-prepend">
                                            <span class="input-group-text font-weight-bold">{{txnAttributes.base_currency}}</span>
                                        </span>
                                        <input type="text"
                                               v-model.number="tax.total"
                                               class="rg-txn-item-row-total form-control form-control-sm text-right"
                                               placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-lg-4 ">
                                    <div class="h-100 align-baseline">
                                        <div class="float-left pt-1 font-weight-semibold">{{tax.name}}</div>
                                        <button type="button"
                                                @click="txnItemsTaxRemove(tax.id)"
                                                class="btn bg-danger bg-transparent text-danger btn-icon float-right"
                                                title="Remove Tax">
                                            <i class="icon-cross3"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">
                                    Payment mode:
                                </label>
                                <div class="col-lg-3">
                                    <model-select
                                        :options="txnPaymentModes"
                                        v-model="txnAttributes.payment_mode"
                                        placeholder="Select payment mode">
                                    </model-select>
                                </div>

                                <div class="col-lg-4">
                                    <div class="row">
                                        <label class="col-auto col-form-label text-right bg-light border rounded-left border-right-0"
                                               style="white-space: nowrap;">
                                            Credit:
                                        </label>
                                        <div class="col pl-0">
                                            <model-list-select :list="txnAccountsPayment"
                                                               v-model="txnAttributes.credit_financial_account_code"
                                                               option-value="id"
                                                               option-text="name"
                                                               class="rounded-left-0"
                                                               placeholder="select account">
                                            </model-list-select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-lg-2 col-form-label">
                                    Description:
                                </label>
                                <div class="col-lg-7">
                                    <textarea v-model="txnAttributes.items[0].description" class="form-control input-roundless" rows="2" placeholder="Description"></textarea>
                                </div>
                            </div>

                        </fieldset>

                        <!-- txn-recurring-fields -->
                        <txn-recurring-fields></txn-recurring-fields>
                        <!-- /txn-recurring-fields -->

                        <fieldset class="mt-3">

                            <!--https://stackoverflow.com/questions/53409139/how-to-upload-multiple-images-files-with-javascript-and-axios-formdata-->
                            <!--https://laracasts.com/discuss/channels/vue/upload-multiple-files-and-relate-them-to-post-model-->
                            <div class="form-group row">
                                <label class="col-form-label col-lg-2">Attach files</label>
                                <div class="col-auto">
                                    <input ref="filesAttached" type="file" multiple class="form-control border-0 pl-0 h-auto">
                                </div>
                            </div>

                        </fieldset>


                        <div class="text-left col-md-10 offset-md-2 p-0">

                            <div class="btn-group ml-1">
                                <button type="button" class="btn btn-outline bg-purple-300 border-purple-300 text-purple-800 btn-icon border-2 dropdown-toggle" data-toggle="dropdown">
                                    <i class="icon-cog"></i>
                                </button>

                                <div class="dropdown-menu dropdown-menu-left">
                                    <a href="/" class="dropdown-item" v-on:click.prevent="txnOnSave('draft', false)">
                                        <i class="icon-file-text3"></i> Save as draft
                                    </a>
                                    <a href="/" class="dropdown-item" v-on:click.prevent="txnOnSave('approved', false)">
                                        <i class="icon-file-check2"></i> Save and approve
                                    </a>
                                    <a href="/" class="dropdown-item" v-on:click.prevent="txnOnSave('approved', true)">
                                        <i class="icon-mention"></i> Save, approve and send
                                    </a>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-danger font-weight-bold">
                                <i class="icon-file-plus2 mr-1"></i>
                                {{txnSubmitBtnText}} |
                                <span class="font-weight-semibold">
                                    {{rgNumberFormat(txnAttributes.total, 2)}}
                                    {{txnAttributes.base_currency}}
                                </span>
                            </button>

                        </div>

                    </form>

                </div>
            </div>
            <!-- /form horizontal -->


        </div>
        <!-- /content area -->

    </div>
    <!-- /main content -->

</template>

<script>

    export default {
        components: {},
        data() {
            return {
                //loadingContactInvoices: false
            }
        },
        computed: {
            receiptTotalDue: function () {
                let currentObj = this;
                let t = 0
                currentObj.txnAttributes.items.forEach(function (item) {
                    //console.log(item.txn)
                    if (typeof item.txn.balance !== 'undefined') {
                        //console.log(item.txn.balance)
                        t += currentObj.rgNumber(item.txn.balance)
                    }
                })
                return t
            }
        },
        watch: {
            'txnAttributes.recurring.date_range': function () {
                let v = this.txnAttributes.recurring.date_range
                // console.log(v)

                //if (v.length > 0) {
                if (typeof v !== 'undefined') {
                    this.txnAttributes.recurring.start_date = v[0]
                    this.txnAttributes.recurring.end_date = v[1]
                }
            }
        },
        created: function () {
            this.pageTitle = 'Create Recurring Expense'
            this.pageAction = 'Create'
        },
        mounted() {
            this.$root.appMenu('accounting')

            this.txnTaxesAllIncludive = true

            //console.log(this.$route.fullPath)
            this.txnCreateData()
            this.txnFetchSuppliers('-initiate-')
            this.txnFetchAccounts('-initiate-')
            this.txnFetchTaxes()
            this.txnFetchAccountsExpenses()
            this.txnFetchAccountsPayment()
            this.txnFetchPaymentModes()

            //this.txnFetchAccounts()
        },
        methods: {
            expenseAccountInput(expense_account_code) {
                //console.log(expense_account_code)
                let a = this.txnAccountsExpenses.find(account => {
                    return account.code === expense_account_code
                })
                //console.log(a)
                this.txnAttributes.debit_financial_account_code = expense_account_code
                this.txnAttributes.items[0].name = a.name
                this.txnAttributes.items[0].description = a.name
            }
        },
        beforeUpdate: function () {
            //
        },
        updated: function () {
            //this.txnComponentUpdates()
        },
        destroyed: function () {
            this.txnTaxesAllIncludive = false
        }
    }
</script>
