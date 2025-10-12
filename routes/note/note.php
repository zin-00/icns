<?php

use App\Http\Controllers\Notes\NoteController;
use Illuminate\Support\Facades\Route;


Route::post('/create/note', [NoteController::class, 'store'])->name('note.create');

