<?php

namespace App\Http\Controllers\Feedback;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
   public function store(Request $request)
    {
        Log::info('Feedback Request:', $request->all());

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
