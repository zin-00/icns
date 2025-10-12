<?php

namespace App\Http\Controllers\Facilty;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FacilityController extends Controller
{

    public function store(Request $request){
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hours' => 'nullable|string|max:255',
            'marker_id' => 'nullable|exists:markers,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        Facility::create($data);

        return redirect()->back()->with('success', 'Facility created successfully.');
    }
    public function update(Request $request, $id){
        $facility = Facility::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'description' => 'nullable|string',
            'hours' => 'nullable|string|max:255',
            'marker_id' => 'nullable|exists:markers,id',
            'status' => 'nullable|in:active,inactive',
        ]);

        $facility->update($data);

       return response()->json([
            'message' => 'Facility updated successfully.',
            'facility' => $facility
        ]);
    }
    public function destroy($id){
        $facility = Facility::findOrFail($id);
        $facility->delete();

       return response()->json([
            'message' => 'Facility deleted successfully.'
        ]);
    }
}
