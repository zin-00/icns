<?php

use App\Http\Controllers\Facilty\FacilityController;
use Illuminate\Support\Facades\Route;


Route::post('/facilities', [FacilityController::class, 'store'])->name('facility.post');
Route::put('/facilities/{id}', [FacilityController::class, 'update'])->name('facility.put');
Route::delete('/facilities/{id}/destroy', [FacilityController::class, 'destroy'])->name('facility.delete');
