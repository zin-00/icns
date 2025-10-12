<?php

namespace App\Http\Controllers\UIRenderer;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Models\Feedback;
use App\Models\Marker;
use App\Models\Note;
use App\Models\Report;
use App\Models\SearchLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RenderController extends Controller
{
    public function login(){
        return Inertia::render('auth/Login');
    }
    public function register(){
        return Inertia::render('auth/Register');
    }
   public function facility(){
        $facilities = Facility::with('marker')->paginate(5);
        $markers = Marker::all();

        return Inertia::render('admin/Facility', [
            'markers' => $markers,
            'facilities' => $facilities->items(),
            'pagination' => [
                'current_page' => $facilities->currentPage(),
                'last_page' => $facilities->lastPage(),
                'per_page' => $facilities->perPage(),
                'total' => $facilities->total(),
                'from' => $facilities->firstItem(),
                'to' => $facilities->lastItem(),
            ],
            'message' => 'Facilities retrieved successfully.',
        ]);
    }

    public function renderMap(){
        return Inertia::render('map/Map');
    }
    public function searchlog(Request $request)
    {
        // Get page number from request (default to 1)
        $page = $request->get('page', 1);
        $perPage = 4; // Items per page

        // Get paginated search logs with guest relationship
        $searchLogs = SearchLog::with('guest')
            ->orderBy('search_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return Inertia::render('logs/SearchLogs', [
            'searchLogs' => $searchLogs->items(),
            'pagination' => [
                'current_page' => $searchLogs->currentPage(),
                'last_page' => $searchLogs->lastPage(),
                'total' => $searchLogs->total(),
                'per_page' => $searchLogs->perPage(),
                'from' => $searchLogs->firstItem(),
                'to' => $searchLogs->lastItem(),
            ]
        ]);
    }
    public function notes(){
        $notes = Note::with(['guest', 'marker'])->get();
        return Inertia::render('notes/Notes', [
            'notes' => $notes
        ]);
    }

     public function feedback(){
        $feedbacks = Feedback::with(['guest', 'marker'])->get();
        return Inertia::render('feedback/Feedback', [
            'feedbacks' => $feedbacks
        ]);
    }

}
