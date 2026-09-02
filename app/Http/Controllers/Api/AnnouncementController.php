<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of announcements.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Announcement::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $perPage = $request->query('per_page', 15);
        
        $announcements = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $announcements
        ]);
    }

    /**
     * Store a newly created announcement.
     */
    public function store(StoreAnnouncementRequest $request): JsonResponse
    {
        $announcement = Announcement::create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement created successfully',
            'data' => $announcement
        ], 201);
    }

    /**
     * Display the specified announcement and record a view.
     */
    public function show(Announcement $announcement): JsonResponse
    {
        $userId = Auth::id();

        if ($userId) {
            $changes = $announcement->viewers()->syncWithoutDetaching([$userId]);
            
            // If the user hasn't viewed it before, increment the total views
            if (!empty($changes['attached'])) {
                $announcement->increment('total_views');
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $announcement
        ]);
    }

    /**
     * Update the specified announcement.
     */
    public function update(UpdateAnnouncementRequest $request, Announcement $announcement): JsonResponse
    {
        $announcement->update($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement updated successfully',
            'data' => $announcement
        ]);
    }

    /**
     * Remove the specified announcement.
     */
    public function destroy(Announcement $announcement): JsonResponse
    {
        $announcement->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement deleted successfully'
        ]);
    }

    /**
     * Get announcement count (unread and total) for the authenticated user.
     */
    public function count(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalCount = Announcement::count();

        $unreadCount = $user ? Announcement::whereDoesntHave('viewers', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->count() : 0;

        return response()->json([
            'status' => 'success',
            'data' => [
                'unread_count' => $unreadCount,
                'total_count' => $totalCount,
            ]
        ]);
    }
}
