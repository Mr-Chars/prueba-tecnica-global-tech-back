<?php

use App\Http\Controllers\api\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => ['cors']], function () {
    Route::controller(EmployeeController::class)->group(function () {
        Route::post('/employee', 'add');
        Route::delete('/employee', 'delete');
        Route::put('/employee', 'update');
        Route::get('/employee', 'search');
    });
});
