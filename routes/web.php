<?php

use App\Http\Controllers\CheckupController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CheckupController::class, 'test']);

Route::post('/changeLang', [CheckupController::class, 'changeLang']);
Route::post('/checkLocation', [CheckupController::class, 'checkLocation']);

Route::get('/walkin', [CheckupController::class, 'walkin']);
Route::post('/walkin/otp', [CheckupController::class, 'walkinOTP']);
Route::post('/walkin/sendotp', [CheckupController::class, 'walkinSendOTP']);
Route::post('/walkin/result', [CheckupController::class, 'walkinResult']);
Route::post('/walkin/genQueue', [CheckupController::class, 'requestQueue']);
Route::get('/walkin/viewqueue/{hn}', [CheckupController::class, 'myQueue']);
Route::get('/walkin/viewapp/{hn}', [CheckupController::class, 'myAPP']);

Route::get('/sms/{hasHN}', [CheckupController::class, 'smsView']);
Route::post('/sms/genQueue', [CheckupController::class, 'requestQueue']);
Route::get('/sms/viewqueue/{hn}', [CheckupController::class, 'myQueue']);