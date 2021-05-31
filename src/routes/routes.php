<?php

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

	Route::prefix('expenses')->group(function () {

        //Route::get('summary', 'Rutatiina\Expense\Http\Controllers\DefaultController@summary');
        Route::post('export-to-excel', 'Rutatiina\Expense\Http\Controllers\DefaultController@exportToExcel');
        Route::post('{id}/approve', 'Rutatiina\Expense\Http\Controllers\DefaultController@approve');
        //Route::post('contact-estimates', 'Rutatiina\Expense\Http\Controllers\Sales\ReceiptController@estimates');
        Route::get('{id}/copy', 'Rutatiina\Expense\Http\Controllers\DefaultController@copy');

    });

    Route::resource('expenses/settings', 'Rutatiina\Expense\Http\Controllers\SettingsController');
    Route::resource('expenses', 'Rutatiina\Expense\Http\Controllers\DefaultController');

});

Route::group(['middleware' => ['web', 'auth', 'tenant', 'service.accounting']], function() {

    Route::prefix('recurring-expenses')->group(function () {

        //Route::get('summary', 'Rutatiina\Expense\Http\Controllers\RecurringController@summary');
        Route::post('export-to-excel', 'Rutatiina\Expense\Http\Controllers\RecurringController@exportToExcel');
        Route::post('{id}/approve', 'Rutatiina\Expense\Http\Controllers\RecurringController@approve');
        //Route::post('contact-estimates', 'Rutatiina\Expense\Http\Controllers\Sales\ReceiptController@estimates');
        Route::get('{id}/copy', 'Rutatiina\Expense\Http\Controllers\RecurringController@copy');

    });

    Route::resource('recurring-expenses/settings', 'Rutatiina\Expense\Http\Controllers\RecurringSettingController');
    Route::resource('recurring-expenses', 'Rutatiina\Expense\Http\Controllers\RecurringController');

});