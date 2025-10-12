<?php

namespace App\Http\Controllers\Notes;

use App\Http\Controllers\Controller;
use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'guest_id' => ['required', 'exists:guests,id'],
            'marker_id' => ['required', 'exists:markers,id'],
            'content' => ['required', 'string'],
        ]);

        $note = Note::create($data);

        return response()->json([
            'message' => 'Note created successfully.',
            'note' => $note
        ], 201);
    }

}
