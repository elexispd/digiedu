<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BadgeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $badges = Badge::withCount('students')->get();
        return response()->json([
            'status' => 'success',
            'data' => $badges
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048', // Allow images up to 2MB
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('badges', 'public');
            $validated['logo'] = Storage::url($path);
        }

        $badge = Badge::create($validated);

        return response()->json([
            'status' => 'success',
            'data' => $badge
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Badge $badge)
    {
        $badge->loadCount('students');
        return response()->json([
            'status' => 'success',
            'data' => $badge
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Badge $badge)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'type' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'level' => 'nullable|string|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($badge->logo) {
                $oldPath = str_replace('/storage/', '', $badge->logo);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('logo')->store('badges', 'public');
            $validated['logo'] = Storage::url($path);
        }

        $badge->update($validated);

        return response()->json([
            'status' => 'success',
            'data' => $badge
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Badge $badge)
    {
        if ($badge->logo) {
            $oldPath = str_replace('/storage/', '', $badge->logo);
            Storage::disk('public')->delete($oldPath);
        }
        
        $badge->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Badge deleted successfully'
        ]);
    }
}
