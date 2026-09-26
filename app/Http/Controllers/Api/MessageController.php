<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Models\Guardian;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    // ──────────────────────────────────────────────────────────────────────────
    // GET /messages/contacts
    // Returns users this authenticated user is allowed to message.
    // Admin   → everyone (paginated)
    // Teacher → their students' guardians + all admins
    // Guardian/Parent → their children's teachers + all admins
    // ──────────────────────────────────────────────────────────────────────────
    public function contacts(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $contacts = $this->adminContacts($user, $request);
        } elseif ($user->hasRole('teacher')) {
            $contacts = $this->teacherContacts($user);
        } else {
            // parent / guardian
            $contacts = $this->guardianContacts($user);
        }

        return response()->json([
            'status' => 'success',
            'data'   => $contacts,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /messages
    // List all conversations for the authenticated user, newest first.
    // ──────────────────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $conversations = Conversation::forUser($user->id)
            ->with([
                'participantOne:id,name',
                'participantTwo:id,name',
                'latestMessage:messages.id,messages.conversation_id,messages.sender_id,messages.body,messages.created_at',
            ])
            ->orderByDesc('last_message_at')
            ->paginate(20);

        $conversations->getCollection()->transform(function (Conversation $conv) use ($user) {
            $other = $conv->otherParticipant($user->id);

            return [
                'id'            => $conv->id,
                'other_user'    => $other ? [
                    'id'   => $other->id,
                    'name' => $other->name,
                    'role' => $other->getRoleNames()->first(),
                ] : null,
                'last_message'  => $conv->latestMessage ? [
                    'body'    => $conv->latestMessage->body,
                    'sent_at' => $conv->latestMessage->created_at,
                ] : null,
                'unread_count'  => $conv->unreadCountFor($user->id),
                'updated_at'    => $conv->last_message_at ?? $conv->updated_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $conversations,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /messages/{conversation}
    // Paginated messages within a conversation (must be a participant).
    // ──────────────────────────────────────────────────────────────────────────
    public function show(Request $request, string $conversationId): JsonResponse
    {
        $user         = $request->user();
        $conversation = $this->resolveConversation($conversationId, $user->id);

        if (!$conversation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Conversation not found or access denied.',
            ], 403);
        }

        $other    = $conversation->otherParticipant($user->id);
        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->orderByDesc('created_at')
            ->paginate(50);

        $messages->getCollection()->transform(function (Message $msg) use ($user) {
            return [
                'id'         => $msg->id,
                'body'       => $msg->body,
                'sender_id'  => $msg->sender_id,
                'sender_name'=> $msg->sender?->name,
                'is_mine'    => $msg->sender_id === $user->id,
                'read_at'    => $msg->read_at,
                'created_at' => $msg->created_at,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => [
                'conversation' => [
                    'id'         => $conversation->id,
                    'other_user' => $other ? [
                        'id'   => $other->id,
                        'name' => $other->name,
                        'role' => $other->getRoleNames()->first(),
                    ] : null,
                ],
                'messages' => $messages,
            ],
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // POST /messages
    // Send a message. Creates a conversation if one doesn't exist yet.
    // Validates that sender is allowed to message the recipient based on role.
    // ──────────────────────────────────────────────────────────────────────────
    public function store(SendMessageRequest $request): JsonResponse
    {
        $sender      = $request->user();
        $recipientId = $request->input('recipient_id');
        $body        = $request->input('body');

        // Prevent self-messaging
        if ($sender->id === $recipientId) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You cannot send a message to yourself.',
            ], 422);
        }

        $recipient = User::find($recipientId);

        if (!$recipient) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Recipient not found.',
            ], 404);
        }

        // Authorise: check the sender is allowed to message this recipient
        $authorised = $this->canMessage($sender, $recipient);

        if (!$authorised) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You are not permitted to send a message to this user.',
            ], 403);
        }

        // Find or create conversation (always store lower UUID first to keep pair unique)
        [$p1, $p2] = $this->orderedPair($sender->id, $recipientId);

        $conversation = DB::transaction(function () use ($p1, $p2, $sender, $body) {
            $conversation = Conversation::firstOrCreate(
                ['participant_one_id' => $p1, 'participant_two_id' => $p2],
                ['id' => (string) Str::uuid(), 'last_message_at' => now()]
            );

            $message = Message::create([
                'id'              => (string) Str::uuid(),
                'conversation_id' => $conversation->id,
                'sender_id'       => $sender->id,
                'body'            => $body,
            ]);

            $conversation->update(['last_message_at' => now()]);

            return ['conversation' => $conversation, 'message' => $message];
        });

        broadcast(new MessageSent($conversation['message']));

        return response()->json([
            'status'  => 'success',
            'message' => 'Message sent.',
            'data'    => [
                'conversation_id' => $conversation['conversation']->id,
                'message'         => [
                    'id'         => $conversation['message']->id,
                    'body'       => $conversation['message']->body,
                    'is_mine'    => true,
                    'read_at'    => null,
                    'created_at' => $conversation['message']->created_at,
                ],
            ],
        ], 201);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // PUT /messages/{conversation}/read
    // Mark all messages from the other participant as read.
    // ──────────────────────────────────────────────────────────────────────────
    public function markRead(Request $request, string $conversationId): JsonResponse
    {
        $user         = $request->user();
        $conversation = $this->resolveConversation($conversationId, $user->id);

        if (!$conversation) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Conversation not found or access denied.',
            ], 403);
        }

        $updated = $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status'  => 'success',
            'message' => "{$updated} message(s) marked as read.",
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GET /messages/unread-count
    // Total number of unread messages across all conversations.
    // ──────────────────────────────────────────────────────────────────────────
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();

        $count = Message::whereHas('conversation', function ($q) use ($user) {
            $q->forUser($user->id);
        })
        ->where('sender_id', '!=', $user->id)
        ->whereNull('read_at')
        ->count();

        return response()->json([
            'status' => 'success',
            'data'   => ['unread_count' => $count],
        ]);
    }

    // ══════════════════════════════════════════════════════════════════════════
    // PRIVATE HELPERS
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Resolve a conversation by ID, ensuring the given user is a participant.
     */
    private function resolveConversation(string $conversationId, string $userId): ?Conversation
    {
        return Conversation::forUser($userId)->find($conversationId);
    }

    /**
     * Normalise a user pair: always store the lexicographically smaller UUID first.
     * This makes the unique constraint on (p1, p2) deterministic regardless of who initiates.
     */
    private function orderedPair(string $a, string $b): array
    {
        return strcmp($a, $b) < 0 ? [$a, $b] : [$b, $a];
    }

    /**
     * Role-aware authorisation: can $sender message $recipient?
     */
    private function canMessage(User $sender, User $recipient): bool
    {
        // Admins can message anyone
        if ($sender->hasRole('admin')) {
            return true;
        }

        // No one can message themselves (already handled above)
        // Admins are always a valid recipient for anyone
        if ($recipient->hasRole('admin')) {
            return true;
        }

        // Teachers can message guardians of their students
        if ($sender->hasRole('teacher')) {
            if ($recipient->hasRole('parent')) {
                return $this->teacherHasSharedStudent($sender, $recipient);
            }
            // Teachers cannot message other teachers directly
            return false;
        }

        // Guardians (parent role) can message teachers of their children
        if ($sender->hasRole('parent')) {
            if ($recipient->hasRole('teacher')) {
                return $this->guardianHasChildWithTeacher($sender, $recipient);
            }
            // Guardians cannot message other guardians directly
            return false;
        }

        return false;
    }

    /**
     * Check whether a teacher shares at least one student with a guardian.
     */
    private function teacherHasSharedStudent(User $teacher, User $recipient): bool
    {
        $guardian = $recipient->guardian;
        if (!$guardian) {
            return false;
        }

        return DB::table('guardian_student')
            ->join('students', 'students.id', '=', 'guardian_student.student_id')
            ->join('subject_assignments', function ($join) use ($teacher) {
                $join->on('subject_assignments.school_class_id', '=', 'students.school_class_id')
                     ->where('subject_assignments.user_id', '=', $teacher->id);
            })
            ->where('guardian_student.guardian_id', $guardian->id)
            ->exists();
    }

    /**
     * Check whether a guardian has at least one child taught by the given teacher.
     */
    private function guardianHasChildWithTeacher(User $guardianUser, User $teacher): bool
    {
        $guardian = $guardianUser->guardian;
        if (!$guardian) {
            return false;
        }

        return DB::table('guardian_student')
            ->join('students', 'students.id', '=', 'guardian_student.student_id')
            ->join('subject_assignments', function ($join) use ($teacher) {
                $join->on('subject_assignments.school_class_id', '=', 'students.school_class_id')
                     ->where('subject_assignments.user_id', '=', $teacher->id);
            })
            ->where('guardian_student.guardian_id', $guardian->id)
            ->exists();
    }

    /**
     * Contacts for an admin: all non-admin users, paginated with search.
     */
    private function adminContacts(User $admin, Request $request): mixed
    {
        $query = User::with('roles:id,name')
            ->where('id', '!=', $admin->id);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        return $query->select('id', 'name')
            ->paginate(20)
            ->through(fn ($u) => [
                'id'   => $u->id,
                'name' => $u->name,
                'role' => $u->getRoleNames()->first(),
            ]);
    }

    /**
     * Contacts for a teacher: guardians of their students + all admins.
     */
    private function teacherContacts(User $teacher): array
    {
        // Admins
        $admins = User::role('admin')->select('id', 'name')->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'role' => 'admin']);

        // Classes taught by teacher
        $classIds = DB::table('subject_assignments')
            ->where('user_id', $teacher->id)
            ->pluck('school_class_id')
            ->unique();

        $guardianUsers = collect();
        if ($classIds->isNotEmpty()) {
            $studentIds = DB::table('students')
                ->whereIn('school_class_id', $classIds)
                ->pluck('id');

            if ($studentIds->isNotEmpty()) {
                $guardianIds = DB::table('guardian_student')
                    ->whereIn('student_id', $studentIds)
                    ->pluck('guardian_id')
                    ->unique();

                $guardianUserIds = DB::table('guardians')
                    ->whereIn('id', $guardianIds)
                    ->pluck('user_id')
                    ->unique();

                $guardianUsers = User::whereIn('id', $guardianUserIds)
                    ->select('id', 'name')
                    ->get()
                    ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'role' => 'parent']);
            }
        }

        return $admins->concat($guardianUsers)->values()->all();
    }

    /**
     * Contacts for a guardian: teachers of their children + all admins.
     */
    private function guardianContacts(User $guardianUser): array
    {
        $guardian = $guardianUser->guardian;

        // Admins always available
        $admins = User::role('admin')->select('id', 'name')->get()
            ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'role' => 'admin']);

        if (!$guardian) {
            return $admins->values()->all();
        }

        // Students linked to this guardian
        $studentIds = DB::table('guardian_student')
            ->where('guardian_id', $guardian->id)
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return $admins->values()->all();
        }

        // Classes of those students
        $classIds = DB::table('students')
            ->whereIn('id', $studentIds)
            ->whereNotNull('school_class_id')
            ->pluck('school_class_id')
            ->unique();

        $teacherUsers = collect();
        if ($classIds->isNotEmpty()) {
            // Teachers assigned to those classes
            $teacherUserIds = DB::table('subject_assignments')
                ->whereIn('school_class_id', $classIds)
                ->pluck('user_id')
                ->unique();

            $teacherUsers = User::whereIn('id', $teacherUserIds)
                ->select('id', 'name')
                ->get()
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'role' => 'teacher']);
        }

        return $admins->concat($teacherUsers)->values()->all();
    }
}
