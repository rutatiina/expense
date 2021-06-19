<?php

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

	Route::prefix('expenses')->group(function () {

        //Route::get('summary', 'Rutatiina\Expense\Http\Controllers\ExpenseController@summary');
        Route::post('export-to-excel', 'Rutatiina\Expense\Http\Controllers\ExpenseController@exportToExcel');
        Route::post('{id}/approve', 'Rutatiina\Expense\Http\Controllers\ExpenseController@approve');
        //Route::post('contact-estimates', 'Rutatiina\Expense\Http\Controllers\Sales\ReceiptController@estimates');
        Route::get('{id}/copy', 'Rutatiina\Expense\Http\Controllers\ExpenseController@copy');

    });

    Route::resource('expenses/settings', 'Rutatiina\Expense\Http\Controllers\ExpenseSettingsController');
    Route::resource('expenses', 'Rutatiina\Expense\Http\Controllers\ExpenseController');

});

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

    Route::prefix('recurring-expenses')->group(function () {

        //Route::get('summary', 'Rutatiina\Expense\Http\Controllers\RecurringExpenseController@summary');
        Route::post('export-to-excel', 'Rutatiina\Expense\Http\Controllers\RecurringExpenseController@exportToExcel');
        Route::post('{id}/activate', 'Rutatiina\Expense\Http\Controllers\RecurringExpenseController@activate');
        //Route::post('contact-estimates', 'Rutatiina\Expense\Http\Controllers\Sales\ReceiptController@estimates');
        Route::get('{id}/copy', 'Rutatiina\Expense\Http\Controllers\RecurringExpenseController@copy');

    });

    Route::resource('recurring-expenses/settings', 'Rutatiina\Expense\Http\Controllers\RecurringExpenseSettingController');
    Route::resource('recurring-expenses', 'Rutatiina\Expense\Http\Controllers\RecurringExpenseController');

});