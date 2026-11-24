<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\UserMatch;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /**
     * Menampilkan halaman personal chat dengan daftar match dinamis dan pesan aktual.
     */
    public function showPersonalChatPage(Request $request)
    {
        $currentUser = Auth::user();

        // Ambil daftar match untuk user yang sedang login
        $myMatches = UserMatch::where(function ($query) use ($currentUser) {
                                $query->where('user1_id', $currentUser->id)
                                      ->orWhere('user2_id', $currentUser->id);
                            })
                            ->with(['user1', 'user2'])
                            ->orderBy('created_at', 'desc') // Urutkan berdasarkan match terbaru
                            ->get();

        $chatListUsers = [];
        $selectedOtherUser = null;
        $activeMatch = null;

        $activeChatUserId = $request->query('with', null);

        // Membangun daftar chat dari matches dengan last message yang sebenarnya
        foreach ($myMatches as $match) {
            $otherUserInMatch = ($match->user1_id === $currentUser->id) ? $match->user2 : $match->user1;

            if ($otherUserInMatch) {
                // Ambil pesan terakhir untuk match ini
                // Urut id, bukan created_at: dua pesan di detik yang sama punya created_at sama.
                $lastMessage = Message::where('match_id', $match->id)
                                    ->latest('id')
                                    ->first();

                $lastMessageText = $lastMessage
                    ? ($lastMessage->sender_id === $currentUser->id ? 'Kamu: ' : '') . Str::limit($lastMessage->message_content, 30)
                    : 'Mulai obrolan!';

                $chatListUsers[] = (object)[
                    'id' => $otherUserInMatch->id,
                    'full_name' => $otherUserInMatch->full_name,
                    'profile_picture' => $otherUserInMatch->profile_picture,
                    'last_message' => $lastMessageText,
                    'last_message_time' => $lastMessage ? $lastMessage->created_at->diffForHumans() : null,
                    'is_active' => ($otherUserInMatch->id == $activeChatUserId),
                    'match_id' => $match->id,
                    'has_unread' => $this->hasUnreadMessages($match->id, $currentUser->id) // Untuk indikator pesan belum dibaca
                ];

                // Set active chat
                if ($otherUserInMatch->id == $activeChatUserId) {
                    $selectedOtherUser = $otherUserInMatch;
                    $activeMatch = $match;
                }
            }
        }

        // Jika tidak ada user spesifik yang diminta, pilih yang pertama
        if (empty($selectedOtherUser) && count($chatListUsers) > 0) {
            $selectedOtherUser = User::find($chatListUsers[0]->id);
            $chatListUsers[0]->is_active = true;
            
            // Cari active match untuk user pertama
            foreach ($myMatches as $match) {
                $otherUser = ($match->user1_id === $currentUser->id) ? $match->user2 : $match->user1;
                if ($otherUser && $otherUser->id === $selectedOtherUser->id) {
                    $activeMatch = $match;
                    break;
                }
            }
        }

        // Ambil pesan aktual dari database
        $messages = collect();
        if (!empty($selectedOtherUser) && !empty($activeMatch)) {
            $messages = Message::where('match_id', $activeMatch->id)
                                ->with('sender') // Eager load sender info
                                ->orderBy('created_at', 'asc')
                                ->get();

            // Mark messages as read untuk receiver
            Message::where('match_id', $activeMatch->id)
                   ->where('receiver_id', $currentUser->id)
                   ->whereNull('read_at')
                   ->update(['read_at' => now()]);
        }

        return view('chat.personal', compact('selectedOtherUser', 'messages', 'currentUser', 'chatListUsers', 'activeMatch'));
    }

    /**
     * Menangani pengiriman pesan baru.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'match_id' => ['required', 'integer', 'exists:matches,id'],
            'message_content' => ['required', 'string', 'max:1000'],
        ], [
            'receiver_id.required' => 'Penerima pesan tidak valid.',
            'match_id.required' => 'Match ID tidak valid.',
            'message_content.required' => 'Pesan tidak boleh kosong.',
            'message_content.max' => 'Pesan terlalu panjang (maksimal 1000 karakter).',
        ]);

        $currentUser = Auth::user();
        $receiverId = $request->receiver_id;
        $matchId = $request->match_id;
        $messageContent = trim($request->message_content);

        // Validasi match
        $match = UserMatch::where('id', $matchId)
                        ->where(function ($query) use ($currentUser, $receiverId) {
                            $query->where(function ($q) use ($currentUser, $receiverId) {
                                $q->where('user1_id', $currentUser->id)->where('user2_id', $receiverId);
                            })->orWhere(function ($q) use ($currentUser, $receiverId) {
                                $q->where('user1_id', $receiverId)->where('user2_id', $currentUser->id);
                            });
                        })
                        ->first();

        if (!$match) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Match tidak valid untuk pengiriman pesan ini.'
            ], 403);
        }

        try {
            // Simpan pesan ke database
            $message = Message::create([
                'sender_id' => $currentUser->id,
                'receiver_id' => $receiverId,
                'match_id' => $matchId,
                'message_content' => $messageContent,
                'read_at' => null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Pesan berhasil dikirim.',
                'sent_message' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'message_content' => $message->message_content,
                    'timestamp' => $message->created_at->format('H:i'),
                    'sender_name' => $currentUser->full_name,
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Error sending message: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengirim pesan.'
            ], 500);
        }
    }

    /**
     * Cek apakah ada pesan yang belum dibaca untuk match tertentu
     */
    private function hasUnreadMessages($matchId, $currentUserId)
    {
        return Message::where('match_id', $matchId)
                     ->where('receiver_id', $currentUserId)
                     ->whereNull('read_at')
                     ->exists();
    }

    /**
     * API endpoint untuk mengambil pesan terbaru (untuk real-time chat jika diperlukan)
     */
    public function getMessages(Request $request)
    {
        $request->validate([
            'match_id' => ['required', 'integer', 'exists:matches,id'],
            'last_message_id' => ['nullable', 'integer']
        ]);

        $currentUser = Auth::user();
        $matchId = $request->match_id;
        $lastMessageId = $request->last_message_id;

        // Verifikasi bahwa user adalah bagian dari match ini
        $match = UserMatch::where('id', $matchId)
                        ->where(function ($query) use ($currentUser) {
                            $query->where('user1_id', $currentUser->id)
                                  ->orWhere('user2_id', $currentUser->id);
                        })
                        ->first();

        if (!$match) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $query = Message::where('match_id', $matchId)->with('sender');
        
        if ($lastMessageId) {
            $query->where('id', '>', $lastMessageId);
        }

        $messages = $query->orderBy('created_at', 'asc')->get();

        // Endpoint ini di-poll saat percakapan terbuka, jadi pesan masuk dianggap sudah dibaca.
        Message::where('match_id', $matchId)
            ->where('receiver_id', $currentUser->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'messages' => $messages->map(function($message) use ($currentUser) {
                return [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'message_content' => $message->message_content,
                    'timestamp' => $message->created_at->format('H:i'),
                    'is_sender' => $message->sender_id === $currentUser->id,
                    'sender_name' => $message->sender->full_name,
                ];
            })
        ]);
    }
}