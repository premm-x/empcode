<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeExcelController;

Route::post('/employee-excel/upload', [EmployeeExcelController::class, 'upload']);
Route::get('/employee-excel/{jobId}/status', [EmployeeExcelController::class, 'status']);
Route::get('/employee-excel/{jobId}/download', [EmployeeExcelController::class, 'download'])->name('employee-excel.download');



Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
