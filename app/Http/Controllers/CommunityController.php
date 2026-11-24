<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage; // ✅ NEW: Untuk file upload
use Illuminate\Validation\ValidationException;
use App\Models\ChatGroup;
use App\Models\GroupMessage;
use App\Models\ChatGroupMember;
use App\Models\PostReaction; // ✅ NEW: Untuk reactions
use App\Models\PostComment; // ✅ NEW: Untuk comments

class CommunityController extends Controller
{
    private const ALLOWED_ATTACHMENT_MIMES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
    ];

    /**
     * Menampilkan halaman community chat dengan data dinamis.
     * ✅ ENHANCED: Better error handling, logging, dan performance optimization
     */
    public function showCommunityChatPage(Request $request)
    {
        try {
            $currentUser = Auth::user();

            // ✅ ENHANCED: Eager loading dengan moderator untuk performance
            $communities = ChatGroup::where('is_approved', true)
                                    ->with('creator:id,full_name,role', 'moderator:id,full_name,role') // ✅ NEW: Load moderator
                                    ->orderBy('name', 'asc')
                                    ->get();

            $activeGroupId = $request->query('group', null);
            $selectedGroup = null;
            $groupMessages = collect(); // Koleksi kosong default

            // ✅ IMPROVED: Validasi input group ID
            if ($activeGroupId && is_numeric($activeGroupId)) {
                $selectedGroup = ChatGroup::where('id', $activeGroupId)
                                          ->where('is_approved', true)
                                          ->with('creator:id,full_name,role', 'moderator:id,full_name,role') // ✅ NEW: Load moderator
                                          ->first();
                
                // ✅ NEW: Log invalid group access attempt
                if (!$selectedGroup) {
                    Log::warning('User attempted to access invalid community group', [
                        'user_id' => $currentUser->id,
                        'requested_group_id' => $activeGroupId,
                        'ip' => $request->ip()
                    ]);
                }
            }

            // Jika tidak ada grup yang dipilih atau grup tidak valid, pilih grup pertama sebagai default
            if (empty($selectedGroup) && $communities->count() > 0) {
                $selectedGroup = $communities->first();
            }

            // ✅ ENHANCED: Eager loading dengan reactions dan comments untuk performance
            if ($selectedGroup) {
                $groupMessages = GroupMessage::where('group_id', $selectedGroup->id)
                                            // Komentar dimuat saat dibuka (loadComments); di sini cukup jumlahnya.
                                            ->with(['sender:id,full_name,role,profile_picture', 'reactions:id,message_id,user_id'])
                                            ->withCount('comments')
                                            ->orderBy('created_at', 'desc')
                                            ->limit(50) // ✅ NEW: Batasi untuk performance
                                            ->get()
                                            ->reverse(); // ✅ NEW: Reverse agar urutan chronological
            }

            // ✅ NEW: Log successful page view untuk analytics
            Log::info('Community page viewed', [
                'user_id' => $currentUser->id,
                'selected_group_id' => $selectedGroup->id ?? null,
                'total_communities' => $communities->count(),
                'total_messages' => $groupMessages->count()
            ]);

            return view('community.index', compact('communities', 'selectedGroup', 'groupMessages', 'currentUser'));

        } catch (\Exception $e) {
            // ✅ NEW: Comprehensive error logging
            Log::error('Error loading community page', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('home')->with('error', 'Terjadi kesalahan saat memuat halaman komunitas. Silakan coba lagi.');
        }
    }

    /**
     * Menangani pengiriman pesan baru di grup chat.
     * ✅ ENHANCED: File upload support dan improved validation
     */
    public function sendGroupMessage(Request $request)
    {
        try {
            // ✅ ENHANCED: Validation dengan file upload support
            $validatedData = $request->validate([
                'group_id' => ['required', 'integer', 'exists:chat_groups,id'],
                'message_content' => ['required_without:attachment', 'nullable', 'string', 'min:1', 'max:5000'], // ✅ NEW: Optional if has attachment
                'attachment' => ['nullable', 'file', 'max:10240', 'mimetypes:' . implode(',', self::ALLOWED_ATTACHMENT_MIMES)],
            ], [
                'attachment.mimetypes' => 'Tipe file tidak didukung. Gunakan gambar, PDF, Word, Excel, atau teks.',
                'group_id.required' => 'Grup komunitas tidak valid.',
                'group_id.exists' => 'Grup komunitas tidak ditemukan.',
                'message_content.required_without' => 'Pesan atau file attachment wajib diisi.', // ✅ NEW: Updated message
                'message_content.min' => 'Pesan terlalu pendek.',
                'message_content.max' => 'Pesan terlalu panjang (maksimal 5000 karakter).',
                'attachment.file' => 'File attachment tidak valid.', // ✅ NEW
                'attachment.max' => 'Ukuran file maksimal 10 MB.', // ✅ NEW
            ]);

            $currentUser = Auth::user();
            $groupId = $validatedData['group_id'];
            $messageContent = trim($validatedData['message_content'] ?? ''); // ✅ NEW: Handle optional content

            // ✅ IMPROVED: Enhanced group verification
            $group = ChatGroup::where('id', $groupId)
                              ->where('is_approved', true)
                              ->first();

            if (!$group) {
                Log::warning('User attempted to post to non-existent or unapproved group', [
                    'user_id' => $currentUser->id,
                    'group_id' => $groupId,
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'status' => 'error', 
                    'message' => 'Grup komunitas tidak ditemukan atau belum disetujui.'
                ], 404);
            }

            // ✅ ENHANCED: Permission check dengan moderator support
            $hasPermission = $this->checkPostPermission($currentUser, $group);
            
            if (!$hasPermission) {
                Log::warning('User attempted unauthorized post to community group', [
                    'user_id' => $currentUser->id,
                    'user_role' => $currentUser->role,
                    'group_id' => $groupId,
                    'group_creator_id' => $group->creator_id,
                    'group_moderator_id' => $group->moderator_id ?? null, // ✅ NEW: Log moderator info
                    'ip' => $request->ip()
                ]);

                return response()->json([
                    'status' => 'error', 
                    'message' => 'Anda tidak memiliki izin untuk memposting di grup ini.'
                ], 403);
            }

            // ✅ NEW: Handle file upload
            $attachmentData = $this->handleFileUpload($request->file('attachment'));

            // ✅ NEW: Content filtering untuk keamanan
            $filteredContent = $this->filterContent($messageContent);

            // ✅ ENHANCED: Create message dengan attachment data
            $message = GroupMessage::create([
                'group_id' => $groupId,
                'sender_id' => $currentUser->id,
                'message_content' => $filteredContent,
                'attachment_path' => $attachmentData['path'] ?? null, // ✅ NEW
                'attachment_type' => $attachmentData['type'] ?? null, // ✅ NEW
                'attachment_name' => $attachmentData['name'] ?? null, // ✅ NEW
                'attachment_size' => $attachmentData['size'] ?? null, // ✅ NEW
            ]);

            // ✅ NEW: Load sender relationship untuk response
            $message->load('sender:id,full_name,role,profile_picture');

            // ✅ ENHANCED: Log successful message creation dengan attachment info
            Log::info('Community message posted successfully', [
                'message_id' => $message->id,
                'user_id' => $currentUser->id,
                'group_id' => $groupId,
                'content_length' => strlen($filteredContent),
                'has_attachment' => !empty($attachmentData['path']) // ✅ NEW
            ]);

            // ✅ ENHANCED: Response dengan attachment data
            return response()->json([
                'status' => 'success',
                'message' => 'Pesan berhasil dipublikasikan.',
                'sent_message' => [
                    'id' => $message->id,
                    'group_id' => $message->group_id,
                    'sender_id' => $message->sender_id,
                    'message_content' => $message->message_content,
                    'created_at' => $message->created_at->format('d M Y, H:i'),
                    'has_attachment' => $message->hasAttachment(), // ✅ NEW
                    'attachment_url' => $message->attachment_url, // ✅ NEW
                    'attachment_name' => $message->attachment_name, // ✅ NEW
                    'attachment_type' => $message->attachment_type, // ✅ NEW
                    'formatted_file_size' => $message->formatted_file_size, // ✅ NEW
                    'sender' => [
                        'id' => $message->sender->id,
                        'full_name' => $message->sender->full_name,
                        'role' => $message->sender->role,
                        'profile_picture' => $message->sender->profile_picture,
                    ],
                    'reactions' => [], // ✅ NEW: Empty array for new message
                    'comments' => [], // ✅ NEW: Empty array for new message
                ]
            ], 200);

        } catch (ValidationException $e) {
            // ✅ NEW: Handle validation errors specifically
            Log::info('Community message validation failed', [
                'user_id' => Auth::id(),
                'errors' => $e->errors(),
                'request_data' => $request->only(['group_id', 'message_content'])
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak valid.',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // ✅ NEW: Handle unexpected errors
            Log::error('Error posting community message', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'user_id' => Auth::id(),
                'request_data' => $request->only(['group_id', 'message_content']),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan server. Silakan coba lagi dalam beberapa saat.'
            ], 500);
        }
    }

    /**
     * ✅ NEW: Add reaction to message
     */
    public function addReaction(Request $request)
    {
        $validatedData = $request->validate([
            'message_id' => ['required', 'integer', 'exists:group_messages,id'],
            'reaction_type' => ['required', 'string', 'in:like,heart,thumbs_up,celebrate'],
        ], [
            'message_id.required' => 'Pesan tidak valid.',
            'message_id.exists' => 'Pesan tidak ditemukan.',
            'reaction_type.required' => 'Jenis reaksi wajib diisi.',
            'reaction_type.in' => 'Jenis reaksi tidak valid.',
        ]);

        if (! $this->findAccessibleMessage($validatedData['message_id'])) {
            return $this->messageNotAccessibleResponse();
        }

        try {
            $currentUser = Auth::user();
            $messageId = $validatedData['message_id'];
            $reactionType = $validatedData['reaction_type'];

            // Check if user already reacted
            $existingReaction = PostReaction::where('message_id', $messageId)
                                           ->where('user_id', $currentUser->id)
                                           ->first();

            if ($existingReaction) {
                if ($existingReaction->reaction_type === $reactionType) {
                    // Remove reaction if same type
                    $existingReaction->delete();
                    $action = 'removed';
                } else {
                    // Update reaction type
                    $existingReaction->update(['reaction_type' => $reactionType]);
                    $action = 'updated';
                }
            } else {
                // Create new reaction
                PostReaction::create([
                    'message_id' => $messageId,
                    'user_id' => $currentUser->id,
                    'reaction_type' => $reactionType,
                ]);
                $action = 'added';
            }

            // Get updated reaction counts
            $message = GroupMessage::find($messageId);
            $reactionCounts = $message->getReactionCounts();

            return response()->json([
                'status' => 'success',
                'action' => $action,
                'reaction_type' => $reactionType,
                'reaction_counts' => $reactionCounts,
                'user_reaction' => $action !== 'removed' ? $reactionType : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding reaction', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->only(['message_id', 'reaction_type'])
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan reaksi.'
            ], 500);
        }
    }

    /**
     * ✅ NEW: Add comment to message
     */
    public function addComment(Request $request)
    {
        $validatedData = $request->validate([
            'message_id' => ['required', 'integer', 'exists:group_messages,id'],
            'comment_content' => ['required', 'string', 'min:1', 'max:1000'],
            'parent_id' => ['nullable', 'integer', 'exists:post_comments,id'],
        ], [
            'message_id.required' => 'Pesan tidak valid.',
            'message_id.exists' => 'Pesan tidak ditemukan.',
            'comment_content.required' => 'Komentar tidak boleh kosong.',
            'comment_content.max' => 'Komentar terlalu panjang (maksimal 1000 karakter).',
            'parent_id.exists' => 'Komentar yang dibalas tidak ditemukan.',
        ]);

        if (! $this->findAccessibleMessage($validatedData['message_id'])) {
            return $this->messageNotAccessibleResponse();
        }

        // Balasan harus menempel pada komentar di pesan yang sama.
        if (! empty($validatedData['parent_id'])
            && ! PostComment::where('id', $validatedData['parent_id'])->where('message_id', $validatedData['message_id'])->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Komentar yang dibalas tidak berada di pesan ini.',
            ], 422);
        }

        try {
            $currentUser = Auth::user();
            $messageId = $validatedData['message_id'];
            $commentContent = trim($validatedData['comment_content']);
            $parentId = $validatedData['parent_id'] ?? null;

            // Content filtering
            $filteredContent = $this->filterContent($commentContent);

            // Create comment
            $comment = PostComment::create([
                'message_id' => $messageId,
                'user_id' => $currentUser->id,
                'comment_content' => $filteredContent,
                'parent_id' => $parentId,
            ]);

            // Load user relationship
            $comment->load('user:id,full_name,profile_picture');

            // Get updated comment count
            $totalComments = PostComment::where('message_id', $messageId)->count();

            return response()->json([
                'status' => 'success',
                'message' => 'Komentar berhasil ditambahkan.',
                'comment' => [
                    'id' => $comment->id,
                    'message_id' => $comment->message_id,
                    'user_id' => $comment->user_id,
                    'comment_content' => $comment->comment_content,
                    'parent_id' => $comment->parent_id,
                    'created_at' => $comment->created_at->format('d M Y, H:i'),
                    'is_reply' => $comment->isReply(),
                    'user' => [
                        'id' => $comment->user->id,
                        'full_name' => $comment->user->full_name,
                        'profile_picture' => $comment->user->profile_picture,
                    ]
                ],
                'total_comments' => $totalComments,
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding comment', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->only(['message_id', 'comment_content', 'parent_id'])
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan komentar.'
            ], 500);
        }
    }

    /**
     * ✅ NEW: Load more comments for a message
     */
    public function loadComments(Request $request)
    {
        $request->validate([
            'message_id' => ['required', 'integer', 'exists:group_messages,id'],
            'offset' => ['nullable', 'integer', 'min:0'],
        ], [
            'message_id.required' => 'Pesan tidak valid.',
            'message_id.exists' => 'Pesan tidak ditemukan.',
            'offset.integer' => 'Offset tidak valid.',
        ]);

        if (! $this->findAccessibleMessage($request->message_id)) {
            return $this->messageNotAccessibleResponse();
        }

        try {
            $messageId = $request->message_id;
            $offset = $request->offset ?? 0;

            $comments = PostComment::where('message_id', $messageId)
                                  ->whereNull('parent_id') // Only top-level comments
                                  ->with([
                                      'user:id,full_name,profile_picture',
                                      'replies' => function($query) {
                                          $query->with('user:id,full_name,profile_picture')
                                                ->orderBy('created_at', 'asc');
                                      }
                                  ])
                                  ->orderBy('created_at', 'asc')
                                  ->offset($offset)
                                  ->limit(10)
                                  ->get();

            $totalComments = PostComment::where('message_id', $messageId)->count();

            return response()->json([
                'status' => 'success',
                'comments' => $comments->map(function($comment) {
                    return [
                        'id' => $comment->id,
                        'comment_content' => $comment->comment_content,
                        'created_at' => $comment->created_at->format('d M Y, H:i'),
                        'user' => [
                            'id' => $comment->user->id,
                            'full_name' => $comment->user->full_name,
                            'profile_picture' => $comment->user->profile_picture,
                        ],
                        'replies' => $comment->replies->map(function($reply) {
                            return [
                                'id' => $reply->id,
                                'comment_content' => $reply->comment_content,
                                'created_at' => $reply->created_at->format('d M Y, H:i'),
                                'user' => [
                                    'id' => $reply->user->id,
                                    'full_name' => $reply->user->full_name,
                                    'profile_picture' => $reply->user->profile_picture,
                                ]
                            ];
                        })
                    ];
                }),
                'total_comments' => $totalComments,
                'has_more' => ($offset + 10) < $totalComments,
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading comments', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->only(['message_id', 'offset'])
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memuat komentar.'
            ], 500);
        }
    }

    /**
     * Pesan hanya boleh diakses jika grupnya sudah disetujui.
     * `exists:` di validasi hanya membuktikan barisnya ada, bukan hak aksesnya.
     */
    private function findAccessibleMessage($messageId): ?GroupMessage
    {
        return GroupMessage::where('id', $messageId)
            ->whereHas('group', fn ($query) => $query->where('is_approved', true))
            ->first();
    }

    private function messageNotAccessibleResponse()
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Pesan tidak ditemukan atau grupnya tidak dapat diakses.',
        ], 404);
    }

    /**
     * ✅ ENHANCED: Check if user has permission to post in the group dengan moderator support
     */
    private function checkPostPermission($user, $group)
    {
        // Admin global dapat posting di semua grup
        if ($user->role === 'admin') {
            return true;
        }

        // Pembuat grup dapat posting
        if ($group->creator_id === $user->id) {
            return true;
        }

        // ✅ NEW: Moderator grup dapat posting
        if ($group->moderator_id === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * ✅ NEW: Handle file upload dengan security checks
     */
    private function handleFileUpload($file)
    {
        if (!$file) {
            return null;
        }

        try {
            // Tipe file sudah divalidasi lewat aturan 'mimetypes' di sendGroupMessage.
            $originalName = $file->getClientOriginalName();
            // Ekstensi diturunkan dari isi file, bukan dari nama kiriman pengguna:
            // "shell.php" berisi header GIF tidak boleh tersimpan sebagai .php di folder publik.
            $extension = $file->guessExtension() ?? 'bin';
            $filename = time() . '_' . uniqid() . '.' . $extension;

            // Store file
            $path = $file->storeAs('community_uploads', $filename, 'public');

            return [
                'path' => 'storage/' . $path,
                'type' => $this->getFileTypeCategory($file->getMimeType()),
                'name' => $originalName,
                'size' => $file->getSize(),
            ];

        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType()
            ]);
            
            throw new \Exception('File upload failed');
        }
    }

    /**
     * ✅ NEW: Categorize file types
     */
    private function getFileTypeCategory($mimeType)
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        } elseif ($mimeType === 'application/pdf') {
            return 'pdf';
        } elseif (str_contains($mimeType, 'word') || str_contains($mimeType, 'document')) {
            return 'document';
        } elseif (str_contains($mimeType, 'excel') || str_contains($mimeType, 'spreadsheet')) {
            return 'spreadsheet';
        } else {
            return 'file';
        }
    }

    /**
     * ✅ NEW: Basic content filtering untuk mencegah spam/inappropriate content
     */
    private function filterContent($content)
    {
        // Jangan escape HTML di sini: Blade {{ }} dan escapeHtml() di JS sudah
        // meng-escape saat render. Baris baru dipertahankan untuk whitespace-pre-wrap.
        return str_replace("\0", '', trim($content));
    }

    /**
     * ✅ ENHANCED: API endpoint untuk mengambil pesan terbaru dengan reactions/comments
     */
    public function getMessages(Request $request)
    {
        try {
            $request->validate([
                'group_id' => ['required', 'integer', 'exists:chat_groups,id'],
                'last_message_id' => ['nullable', 'integer']
            ]);

            $currentUser = Auth::user();
            $groupId = $request->group_id;
            $lastMessageId = $request->last_message_id;

            // Verifikasi bahwa grup exists dan approved
            $group = ChatGroup::where('id', $groupId)
                              ->where('is_approved', true)
                              ->first();

            if (!$group) {
                return response()->json([
                    'status' => 'error', 
                    'message' => 'Grup tidak ditemukan atau tidak dapat diakses.'
                ], 404);
            }

            $query = GroupMessage::where('group_id', $groupId)
                                 ->with([
                                     'sender:id,full_name,role,profile_picture',
                                     'reactions.user:id,full_name', // ✅ NEW: Load reactions
                                     'comments.user:id,full_name,profile_picture' // ✅ NEW: Load comments
                                 ]);
            
            if ($lastMessageId) {
                $query->where('id', '>', $lastMessageId);
            }

            $messages = $query->orderBy('created_at', 'asc')
                             ->limit(50)
                             ->get();

            return response()->json([
                'status' => 'success',
                'messages' => $messages->map(function($message) {
                    return [
                        'id' => $message->id,
                        'sender_id' => $message->sender_id,
                        'message_content' => $message->message_content,
                        'created_at' => $message->created_at->format('d M Y, H:i'),
                        'has_attachment' => $message->hasAttachment(), // ✅ NEW
                        'attachment_url' => $message->attachment_url, // ✅ NEW
                        'attachment_name' => $message->attachment_name, // ✅ NEW
                        'attachment_type' => $message->attachment_type, // ✅ NEW
                        'reaction_counts' => $message->getReactionCounts(), // ✅ NEW
                        'total_comments' => $message->comments->count(), // ✅ NEW
                        'sender' => [
                            'id' => $message->sender->id,
                            'full_name' => $message->sender->full_name,
                            'role' => $message->sender->role,
                            'profile_picture' => $message->sender->profile_picture,
                        ]
                    ];
                })
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching community messages', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
                'request_data' => $request->only(['group_id', 'last_message_id'])
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil pesan terbaru.'
            ], 500);
        }
    }

    /**
     * ✅ NEW: Get community statistics (untuk admin dashboard - future feature)
     */
    public function getStats()
    {
        try {
            $currentUser = Auth::user();
            
            if ($currentUser->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 403);
            }

            $stats = [
                'total_communities' => ChatGroup::where('is_approved', true)->count(),
                'pending_communities' => ChatGroup::where('is_approved', false)->count(),
                'total_messages' => GroupMessage::count(),
                'active_users' => GroupMessage::distinct('sender_id')
                                              ->where('created_at', '>=', now()->subDays(30))
                                              ->count(),
                'messages_today' => GroupMessage::whereDate('created_at', today())->count(),
            ];

            return response()->json([
                'status' => 'success',
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching community stats', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil statistik.'
            ], 500);
        }
    }
}