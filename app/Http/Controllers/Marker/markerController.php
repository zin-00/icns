<?php

namespace App\Http\Controllers\Marker;

use App\Http\Controllers\Controller;
use App\Models\Marker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class markerController extends Controller
{
    public function index(){
        $markers = Marker::all();
        return response()->json($markers);
    }
       public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'label' => 'required|string|max:255',
            'type' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $marker = Marker::create([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'label' => $request->label,
            'type' => $request->type,
        ]);

        return response()->json([
            'message' => 'New marker saved!',
            'marker' => $marker
        ]);
    }

    public function destroy(Request $request, $id){
        $marker = Marker::findOrFail($id);

        if(!$marker){
            return response()->json([
                'message' => 'It does not exist.'
            ], 404);
        }

        return response()->json([
            'marker' => $marker,
            'message' => 'Marker has been removed'
        ]);
    }
}
