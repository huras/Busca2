<?php

Route::post('/login', 'Auth\LoginAdminController@login');
Route::get('/logout', 'LogoutController@logout');

Route::middleware('auth:admin')->group(function () {
    Route::get('/', 'UsersController@list')->name('clientes');
    Route::post('/users/update/{id}', 'UsersController@update');
});

/**
 * Pastas
 */
Route::group(['as' => 'folders.', 'prefix' => 'folders'], function () {
    Route::get('paginate/{limit}', 'Client\FoldersController@index')->name('index');
    Route::post('', 'Client\FoldersController@store')->name('store');
    Route::get('{id}', 'Client\FoldersController@show')->name('show');
    Route::put('{id}', 'Client\FoldersController@update')->name('update');
    Route::delete('{id}', 'Client\FoldersController@destroy')->name('destroy');
});

/**
 * Arquivos
 */
Route::group(['as' => 'files.', 'prefix' => 'files'], function () {
    Route::get('{idFolder}/{limit?}', 'Client\FilesController@index')->name('index');
    Route::post('', 'Client\FilesController@store')->name('store');
    Route::delete('{id}', 'Client\FilesController@destroy')->name('destroy');

    Route::get('download/{id}', 'Client\FilesController@download')->name('download');
});
