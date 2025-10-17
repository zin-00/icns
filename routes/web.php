<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Facilty\FacilityController;
use App\Http\Controllers\Feedback\FeedbackController;
use App\Http\Controllers\Guest\GuestController;
use App\Http\Controllers\Logs\SearchLogsController;
use App\Http\Controllers\Marker\markerController;
use App\Http\Controllers\Notes\NoteController;
use App\Http\Controllers\Route\RouteController;
use App\Http\Controllers\UIRenderer\RenderController;
use App\Models\Facility;
use App\Models\Marker;
use App\Models\Note;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;








Route::get('/', function () {
    $facilities = Facility::with(['marker.notes.guest'])->get();

    // Get all notes (frontend will filter by guest_id)
    $notes = Note::with(['marker', 'guest'])
        ->get()
        ->map(function($note) {
            return [
                'id' => $note->id,
                'content' => $note->content,
                'marker_id' => $note->marker_id,
                'guest_id' => $note->guest_id,
                'created_at' => $note->created_at,
                'updated_at' => $note->updated_at,
                'guest' => $note->guest ? [
                    'id' => $note->guest->id,
                    'name' => $note->guest->name
                ] : null
            ];
        });

    return Inertia::render('guest/Index', [
        'facilities' => $facilities,
        'notes' => $notes,
    ]);
});
Route::post('/create/feedback', [FeedbackController::class, 'store'])->name('feedback.create');
Route::post('/search-logs', [SearchLogsController::class, 'store'])->name('search.create');

Route::get('/private-routes', [RouteController::class, 'index']);
Route::get('/routes/export/geojson-pub', [RouteController::class, 'exportGeoJSON']);


Route::post('/guests', [GuestController::class, 'store'])->name('guest');

Route::get('/login', [RenderController::class, 'login'])->name('login');
Route::post('/auth/check-email', [AuthController::class, 'isEmailExist'])->name('email.exist');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

Route::get('/register', [RenderController::class, 'register'])->name('register.get');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

Route::middleware('auth')->group(function () {
    Route::get('/notes', [RenderController::class, 'notes'])->name('notes.index');
    Route::get('/dashboard', [RenderController::class, 'dashboard'])->name('dashboard');
       // Facility Routes - FIXED
    Route::get('/facilities', [RenderController::class, 'facility'])->name('facility.get');

    Route::get('/map', [RenderController::class, 'renderMap'])->name('map.get');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::middleware('auth')->group(function () {
    require __DIR__.'/marker/marker.php';
    require __DIR__.'/facility/facility.php';
    require __DIR__.'/feedback/feedback.php';
    require __DIR__.'/logs/searchlog.php';
    require __DIR__.'/auth/auth.php';
    require __DIR__.'/report/report.php';
    require __DIR__.'/route/route.php';
    require __DIR__.'/auth/profile/profile.php';

    Route::get('/search-logs', [RenderController::class, 'searchlog'])->name('logs.index');

});
    require __DIR__.'/note/note.php';


