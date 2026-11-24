<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class GroupMessage extends Model
{
    use HasFactory;

    protected $table = 'group_messages';

    protected $fillable = [
        'group_id',
        'sender_id',
        'message_content',
        'attachment_path',
        'attachment_type',
        'attachment_name',
        'attachment_size',
    ];

    /**
     * Group that owns this message
     */
    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }

    /**
     * User who sent this message
     */
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * Reactions to this message
     */
    public function reactions()
    {
        return $this->hasMany(PostReaction::class, 'message_id');
    }

    /**
     * Comments on this message
     */
    public function comments()
    {
        return $this->hasMany(PostComment::class, 'message_id');
    }

    /**
     * ✅ ENHANCED: Get reaction counts dengan lebih detail
     */
    public function getReactionCounts()
    {
        return $this->reactions()
                   ->selectRaw('reaction_type, count(*) as count')
                   ->groupBy('reaction_type')
                   ->pluck('count', 'reaction_type')
                   ->toArray();
    }

    /**
     * ✅ ENHANCED: Check if specific user has reacted dengan type
     */
    public function hasUserReacted($userId, $reactionType = null)
    {
        $query = $this->reactions()->where('user_id', $userId);
        
        if ($reactionType) {
            $query->where('reaction_type', $reactionType);
        }
        
        return $query->exists();
    }

    /**
     * ✅ NEW: Get user's reaction type for this message
     */
    public function getUserReactionType($userId)
    {
        $reaction = $this->reactions()
                        ->where('user_id', $userId)
                        ->first();
        
        return $reaction ? $reaction->reaction_type : null;
    }

    /**
     * ✅ ENHANCED: Get total reactions count
     */
    public function getTotalReactionsAttribute()
    {
        return $this->reactions()->count();
    }

    /**
     * ✅ ENHANCED: Get total comments count
     */
    public function getTotalCommentsAttribute()
    {
        return $this->comments()->count();
    }

    /**
     * ✅ NEW: Get recent reactions dengan user info
     */
    public function getRecentReactions($limit = 5)
    {
        return $this->reactions()
                   ->with('user:id,full_name,profile_picture')
                   ->latest()
                   ->limit($limit)
                   ->get()
                   ->groupBy('reaction_type');
    }

    /**
     * ✅ NEW: Get top-level comments dengan replies
     */
    public function getTopLevelComments($limit = 10)
    {
        return $this->comments()
                   ->whereNull('parent_id')
                   ->with([
                       'user:id,full_name,profile_picture',
                       'replies' => function($query) {
                           $query->with('user:id,full_name,profile_picture')
                                 ->orderBy('created_at', 'asc');
                       }
                   ])
                   ->orderBy('created_at', 'asc')
                   ->limit($limit)
                   ->get();
    }

    /**
     * Get attachment URL
     */
    public function getAttachmentUrlAttribute()
    {
        return $this->attachment_path ? asset($this->attachment_path) : null;
    }

    /**
     * Link post/reel Instagram pertama di isi pesan, dinormalisasi untuk embed.js.
     * Mengenali instagram.com/[username/]p|reel|tv/{kode} (web menyisipkan username),
     * dan hanya membangun ulang dari karakter aman, jadi aman dipakai di atribut HTML.
     */
    public function instagramUrl(): ?string
    {
        if (! preg_match('~https?://(?:www\.)?instagram\.com/(?:[A-Za-z0-9._]+/)?(p|reel|tv)/([A-Za-z0-9_-]+)~', (string) $this->message_content, $match)) {
            return null;
        }

        return "https://www.instagram.com/{$match[1]}/{$match[2]}/";
    }

    /**
     * Isi pesan tanpa link Instagram yang sudah ditampilkan sebagai embed.
     */
    public function contentWithoutInstagramUrl(): string
    {
        if (! $this->instagramUrl()) {
            return (string) $this->message_content;
        }

        return trim(preg_replace('~https?://(?:www\.)?instagram\.com/(?:[A-Za-z0-9._]+/)?(?:p|reel|tv)/[A-Za-z0-9_-]+\S*~', '', $this->message_content, 1));
    }

    /**
     * Check if message has attachment
     */
    public function hasAttachment()
    {
        return !empty($this->attachment_path);
    }

    /**
     * ✅ ENHANCED: Get formatted file size with better formatting
     */
    public function getFormattedFileSizeAttribute()
    {
        if (!$this->attachment_size) return null;
        
        $bytes = $this->attachment_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * ✅ NEW: Check if attachment is an image
     */
    public function isImageAttachment()
    {
        return $this->attachment_type === 'image';
    }

    /**
     * ✅ NEW: Check if attachment is a document
     */
    public function isDocumentAttachment()
    {
        return in_array($this->attachment_type, ['pdf', 'document', 'spreadsheet']);
    }

    /**
     * ✅ NEW: Get attachment icon based on type
     */
    public function getAttachmentIconAttribute()
    {
        switch ($this->attachment_type) {
            case 'image':
                return 'fas fa-image';
            case 'pdf':
                return 'fas fa-file-pdf';
            case 'document':
                return 'fas fa-file-word';
            case 'spreadsheet':
                return 'fas fa-file-excel';
            default:
                return 'fas fa-file';
        }
    }

    /**
     * ✅ NEW: Get attachment color class based on type
     */
    public function getAttachmentColorAttribute()
    {
        switch ($this->attachment_type) {
            case 'image':
                return 'text-green-500';
            case 'pdf':
                return 'text-red-500';
            case 'document':
                return 'text-blue-500';
            case 'spreadsheet':
                return 'text-emerald-500';
            default:
                return 'text-gray-500';
        }
    }

    /**
     * ✅ NEW: Delete attachment file dari storage
     */
    public function deleteAttachment()
    {
        if ($this->attachment_path) {
            $storagePath = str_replace('storage/', 'public/', $this->attachment_path);
            
            if (Storage::exists($storagePath)) {
                Storage::delete($storagePath);
            }
            
            $this->update([
                'attachment_path' => null,
                'attachment_type' => null,
                'attachment_name' => null,
                'attachment_size' => null,
            ]);
            
            return true;
        }
        
        return false;
    }

    /**
     * ✅ NEW: Check if user can edit this message
     */
    public function canBeEditedBy($user)
    {
        // Message author can edit
        if ($this->sender_id === $user->id) {
            return true;
        }
        
        // Admin can edit any message
        if ($user->role === 'admin') {
            return true;
        }
        
        // Group moderator can edit messages in their group
        if ($this->group && $this->group->moderator_id === $user->id) {
            return true;
        }
        
        return false;
    }

    /**
     * ✅ NEW: Check if user can delete this message
     */
    public function canBeDeletedBy($user)
    {
        // Same permissions as edit
        return $this->canBeEditedBy($user);
    }

    /**
     * ✅ NEW: Soft delete dengan cleanup
     */
    public function softDelete()
    {
        // Delete attachment file
        $this->deleteAttachment();
        
        // Update message content
        $this->update([
            'message_content' => '[Pesan telah dihapus]',
            'attachment_path' => null,
            'attachment_type' => null,
            'attachment_name' => null,
            'attachment_size' => null,
        ]);
        
        return true;
    }

    /**
     * ✅ NEW: Get message summary untuk notifications
     */
    public function getSummaryAttribute()
    {
        if ($this->hasAttachment()) {
            return "📎 {$this->attachment_name}";
        }
        
        return \Illuminate\Support\Str::limit($this->message_content, 50);
    }

    /**
     * ✅ NEW: Scope untuk messages dengan attachments
     */
    public function scopeWithAttachments($query)
    {
        return $query->whereNotNull('attachment_path');
    }

    /**
     * ✅ NEW: Scope untuk messages dari moderator atau admin
     */
    public function scopeFromModerators($query)
    {
        return $query->whereHas('sender', function($q) {
            $q->where('role', 'admin')
              ->orWhereHas('moderatedGroups'); // Assuming reverse relationship
        });
    }

    /**
     * ✅ NEW: Get engagement score (reactions + comments)
     */
    public function getEngagementScoreAttribute()
    {
        return $this->total_reactions + ($this->total_comments * 2); // Comments worth more
    }
}