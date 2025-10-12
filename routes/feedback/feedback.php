<?php

use App\Http\Controllers\Feedback\FeedbackController;
use App\Http\Controllers\UIRenderer\RenderController;
use Illuminate\Support\Facades\Route;


Route::get('/feedback', [RenderController::class, 'feedback'])->name('feedback.index');
