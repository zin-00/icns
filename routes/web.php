<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\UIRenderer\RenderController;
use Illuminate\Support\Facades\Route;



Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [RenderController::class, 'login'])->name('login.get');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/register', [RenderController::class, 'register'])->name('register.get');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

