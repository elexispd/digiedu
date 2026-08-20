<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Enums\TermEnum;
use App\Http\Requests\StoreAcademicSessionRequest;
use App\Http\Requests\UpdateAcademicSessionRequest;
use App\Http\Requests\SetCurrentSessionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AcademicSessionController extends Controller
{
    /**
     * Get all terms (1, 2, 3).
     */
    public function getTerms(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => TermEnum::values()
        ]);
    }

    /**
     * Display a listing of the sessions.
     */
    public function index(): JsonResponse
    {
        $sessions = AcademicSession::orderBy('name', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $sessions
        ]);
    }

    /**
     * Store a newly created session in storage.
     */
    public function store(StoreAcademicSessionRequest $request): JsonResponse
    {
        $session = AcademicSession::create([
            'name' => $request->name
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Academic session created successfully',
            'data' => $session
        ], 201);
    }

    /**
     * Update the specified session.
     */
    public function update(UpdateAcademicSessionRequest $request, string $id): JsonResponse
    {
        $session = AcademicSession::find($id);

        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session not found'
            ], 404);
        }

        $session->update(['name' => $request->name]);

        return response()->json([
            'status' => 'success',
            'message' => 'Session updated successfully',
            'data' => $session
        ]);
    }

    /**
     * Set a session as the current session, and specify the current term.
     */
    public function setCurrent(SetCurrentSessionRequest $request, string $id): JsonResponse
    {
        $session = AcademicSession::find($id);

        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            // Unset all other sessions
            AcademicSession::where('id', '!=', $id)->update([
                'is_current' => false,
                'current_term' => null
            ]);

            // Set this session as current with the specified term
            $session->update([
                'is_current' => true,
                'current_term' => $request->term
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Current session and term updated successfully',
                'data' => $session
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update current session'
            ], 500);
        }
    }

    /**
     * Remove the specified session from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $session = AcademicSession::find($id);

        if (!$session) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session not found'
            ], 404);
        }

        if ($session->is_current) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete the currently active session. Please set another session as active first.'
            ], 403);
        }

        $session->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Session deleted successfully'
        ]);
    }
}
