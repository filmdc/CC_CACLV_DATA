<?php

Route::group(['prefix' => 'v1', 'as' => 'api.', 'namespace' => 'Api\V1\Admin', ], function () {
    // Contract
    Route::post('contracts/media', 'ContractApiController@storeMedia')->name('contracts.storeMedia');
    Route::apiResource('contracts', 'ContractApiController');

    // Program
    Route::apiResource('programs', 'ProgramApiController');

    // Funder
    Route::apiResource('funders', 'FunderApiController');

    // Report
    Route::post('reports/media', 'ReportApiController@storeMedia')->name('reports.storeMedia');
    Route::apiResource('reports', 'ReportApiController');

    //ReportDueDates
    Route::post('/add-to-calendar', 'CalendarDueDateController@addToCalendar');

});

