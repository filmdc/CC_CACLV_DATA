<?php

Route::view('/', 'welcome');
Auth::routes();

Route::group(['prefix' => 'admin', 'as' => 'admin.', 'namespace' => 'Admin', 'middleware' => ['auth', 'admin']], function () {
    Route::get('/', 'HomeController@index')->name('home');
    // Permissions
    Route::delete('permissions/destroy', 'PermissionsController@massDestroy')->name('permissions.massDestroy');
    Route::resource('permissions', 'PermissionsController');

    // Roles
    Route::delete('roles/destroy', 'RolesController@massDestroy')->name('roles.massDestroy');
    Route::resource('roles', 'RolesController');

    // Users
    Route::delete('users/destroy', 'UsersController@massDestroy')->name('users.massDestroy');
    Route::resource('users', 'UsersController');

    // Contract
    Route::delete('contracts/destroy', 'ContractController@massDestroy')->name('contracts.massDestroy');
    Route::post('contracts/media', 'ContractController@storeMedia')->name('contracts.storeMedia');
    Route::post('contracts/ckmedia', 'ContractController@storeCKEditorImages')->name('contracts.storeCKEditorImages');
    Route::post('contracts/parse-csv-import', 'ContractController@parseCsvImport')->name('contracts.parseCsvImport');
    Route::post('contracts/process-csv-import', 'ContractController@processCsvImport')->name('contracts.processCsvImport');
    Route::resource('contracts', 'ContractController');

    // Program
    Route::delete('programs/destroy', 'ProgramController@massDestroy')->name('programs.massDestroy');
    Route::post('programs/parse-csv-import', 'ProgramController@parseCsvImport')->name('programs.parseCsvImport');
    Route::post('programs/process-csv-import', 'ProgramController@processCsvImport')->name('programs.processCsvImport');
    Route::resource('programs', 'ProgramController');

    // Funder
    Route::delete('funders/destroy', 'FunderController@massDestroy')->name('funders.massDestroy');
    Route::post('funders/parse-csv-import', 'FunderController@parseCsvImport')->name('funders.parseCsvImport');
    Route::post('funders/process-csv-import', 'FunderController@processCsvImport')->name('funders.processCsvImport');
    Route::resource('funders', 'FunderController');

    // Report Due Date
    Route::delete('report-due-dates/destroy', 'ReportDueDateController@massDestroy')->name('report-due-dates.massDestroy');
    Route::post('report-due-dates/media', 'ReportDueDateController@storeMedia')->name('report-due-dates.storeMedia');
    Route::post('report-due-dates/ckmedia', 'ReportDueDateController@storeCKEditorImages')->name('report-due-dates.storeCKEditorImages');
    Route::resource('report-due-dates', 'ReportDueDateController');

    // Due Date Email
    Route::delete('due-date-emails/destroy', 'DueDateEmailController@massDestroy')->name('due-date-emails.massDestroy');
    Route::post('due-date-emails/parse-csv-import', 'DueDateEmailController@parseCsvImport')->name('due-date-emails.parseCsvImport');
    Route::post('due-date-emails/process-csv-import', 'DueDateEmailController@processCsvImport')->name('due-date-emails.processCsvImport');
    Route::resource('due-date-emails', 'DueDateEmailController');

    // Audit Logs
    Route::resource('audit-logs', 'AuditLogsController', ['except' => ['create', 'store', 'edit', 'update', 'destroy']]);

    // Report
    Route::delete('reports/destroy', 'ReportController@massDestroy')->name('reports.massDestroy');
    Route::post('reports/media', 'ReportController@storeMedia')->name('reports.storeMedia');
    Route::post('reports/ckmedia', 'ReportController@storeCKEditorImages')->name('reports.storeCKEditorImages');
    Route::post('reports/parse-csv-import', 'ReportController@parseCsvImport')->name('reports.parseCsvImport');
    Route::post('reports/process-csv-import', 'ReportController@processCsvImport')->name('reports.processCsvImport');
    Route::resource('reports', 'ReportController');



    Route::get('global-search', 'GlobalSearchController@search')->name('globalSearch');
});
Route::group(['prefix' => 'profile', 'as' => 'profile.', 'namespace' => 'Auth', 'middleware' => ['auth']], function () {
    // Change password
    if (file_exists(app_path('Http/Controllers/Auth/ChangePasswordController.php'))) {
        Route::get('password', 'ChangePasswordController@edit')->name('password.edit');
        Route::post('password', 'ChangePasswordController@update')->name('password.update');
        Route::post('profile', 'ChangePasswordController@updateProfile')->name('password.updateProfile');
        Route::post('profile/destroy', 'ChangePasswordController@destroy')->name('password.destroyProfile');
    }
});
Route::group(['as' => 'frontend.', 'namespace' => 'Frontend', 'middleware' => ['auth']], function () {
    Route::get('/home', 'HomeController@index')->name('home');
    

    // Permissions
    Route::delete('permissions/destroy', 'PermissionsController@massDestroy')->name('permissions.massDestroy');
    Route::resource('permissions', 'PermissionsController');

    // Roles
    Route::delete('roles/destroy', 'RolesController@massDestroy')->name('roles.massDestroy');
    Route::resource('roles', 'RolesController');

    // Users
    Route::delete('users/destroy', 'UsersController@massDestroy')->name('users.massDestroy');
    Route::resource('users', 'UsersController');

    // Contract
    Route::delete('contracts/destroy', 'ContractController@massDestroy')->name('contracts.massDestroy');
    Route::post('contracts/media', 'ContractController@storeMedia')->name('contracts.storeMedia');
    Route::post('contracts/ckmedia', 'ContractController@storeCKEditorImages')->name('contracts.storeCKEditorImages');
    Route::get('contracts/monitor','ContractController@monitor')->name('contracts.monitor');
    Route::patch('contracts/toggleArchive/{id}', 'ContractController@toggleArchive')->name('contracts.toggleArchive');
    //Route::patch('contracts/{id}/toggleArchive', [ContractController::class, 'toggleArchive'])->name('contracts.toggleArchive');
    Route::get('/contracts/archived', 'ContractController@archivedContracts')->name('contracts.archived');
    Route::get('contracts/export', [\App\Http\Controllers\Frontend\ContractController::class, 'exportContractsToText'])->name('contracts.export');
    Route::get('contracts/export/form', [\App\Http\Controllers\Frontend\ContractController::class, 'showExportForm'])->name('contracts.export.form');
 

    Route::resource('contracts', 'ContractController');


    // Program
    Route::delete('programs/destroy', 'ProgramController@massDestroy')->name('programs.massDestroy');
    Route::resource('programs', 'ProgramController');

    // Funder
    Route::delete('funders/destroy', 'FunderController@massDestroy')->name('funders.massDestroy');
    Route::resource('funders', 'FunderController');

    // Report Due Date
    Route::delete('report-due-dates/destroy', 'ReportDueDateController@massDestroy')->name('report-due-dates.massDestroy');
    Route::post('report-due-dates/media', 'ReportDueDateController@storeMedia')->name('report-due-dates.storeMedia');
    Route::post('report-due-dates/ckmedia', 'ReportDueDateController@storeCKEditorImages')->name('report-due-dates.storeCKEditorImages');
    Route::resource('report-due-dates', 'ReportDueDateController');
    


    // Due Date Email
    Route::delete('due-date-emails/destroy', 'DueDateEmailController@massDestroy')->name('due-date-emails.massDestroy');
    Route::resource('due-date-emails', 'DueDateEmailController');

    // Report
    Route::delete('reports/destroy', 'ReportController@massDestroy')->name('reports.massDestroy');
    Route::post('reports/media', 'ReportController@storeMedia')->name('reports.storeMedia');
    Route::post('reports/ckmedia', 'ReportController@storeCKEditorImages')->name('reports.storeCKEditorImages');
    Route::get('reports/createspecific', 'ReportController@createspecific')->name('reports.createspecific');
    Route::resource('reports', 'ReportController');



  


    Route::get('frontend/profile', 'ProfileController@index')->name('profile.index');
    Route::post('frontend/profile', 'ProfileController@update')->name('profile.update');
    Route::post('frontend/profile/destroy', 'ProfileController@destroy')->name('profile.destroy');
    Route::post('frontend/profile/password', 'ProfileController@password')->name('profile.password');
});
