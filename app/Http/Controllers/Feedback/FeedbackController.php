<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request){
        $data = $request->validate([
            'guest_id' => ['required', 'exists:guests,id'],
            'marker_id' => ['required', 'exists:markers,id'],
            'message' => ['required', 'string']
        ]);

        $feedback = Feedback::create($data);

        return response()->json([
            'message' => 'Feedback added.',
            'feedback' => $feedback
        ]);
    }
}
