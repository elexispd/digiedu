<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Http\Requests\UpdateAnnouncementRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        $user = $request->user();
        if ($user) {
            $announcementIds = $announcements->pluck('id')->toArray();
            $viewedIds = DB::table('announcement_user_views')
                ->where('user_id', $user->id)
                ->whereIn('announcement_id', $announcementIds)
                ->pluck('announcement_id')
                ->flip()
                ->toArray();

            $announcements->getCollection()->transform(function ($announcement) use ($viewedIds) {
                $isRead = isset($viewedIds[$announcement->id]);
                $announcement->is_read = $isRead;
                $announcement->isRead = $isRead;
                return $announcement;
            });
        } else {
            $announcements->getCollection()->transform(function ($announcement) {
                $announcement->is_read = false;
                $announcement->isRead = false;
                return $announcement;
            });
        }

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
            $announcement->is_read = true;
            $announcement->isRead = true;
        } else {
            $announcement->is_read = false;
            $announcement->isRead = false;
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
                'unreadCount' => $unreadCount,
                'total_count' => $totalCount,
                'totalCount' => $totalCount,
            ]
        ]);
    }

    /**
     * Mark a single announcement as read.
     */
    public function markRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $announcement = Announcement::findOrFail($id);

        if ($user && !$announcement->viewers()->where('user_id', $user->id)->exists()) {
            $announcement->viewers()->attach($user->id, [
                'id' => (string) Str::uuid(),
                'viewed_at' => now()
            ]);
            $announcement->increment('total_views');
        }

        $announcement->is_read = true;
        $announcement->isRead = true;

        return response()->json([
            'status' => 'success',
            'message' => 'Announcement marked as read.',
            'data' => $announcement
        ]);
    }

    /**
     * Mark all announcements as read.
     */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            $announcements = Announcement::all();
            foreach ($announcements as $announcement) {
                if (!$announcement->viewers()->where('user_id', $user->id)->exists()) {
                    $announcement->viewers()->attach($user->id, [
                        'id' => (string) Str::uuid(),
                        'viewed_at' => now()
                    ]);
                    $announcement->increment('total_views');
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'All announcements marked as read.'
        ]);
    }
}
