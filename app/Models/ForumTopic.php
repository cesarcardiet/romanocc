<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ForumTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'user_id',
        'comments_count'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the forum topic.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all comments for the forum topic.
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Get only main comments (not replies) for the forum topic.
     */
    public function mainComments(): MorphMany
    {
        return $this->comments()->mainComments();
    }

    /**
     * Scope to order topics by latest first
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to get topics with user information
     */
    public function scopeWithUser($query)
    {
        return $query->with('user');
    }

    /**
     * Scope to get topics with comments count
     */
    public function scopeWithCommentsCount($query)
    {
        return $query->withCount('comments');
    }

    /**
     * Scope to get topics with main comments and their replies
     */
    public function scopeWithCommentsAndReplies($query)
    {
        return $query->with(['mainComments' => function($query) {
            $query->with(['user', 'replies.user'])->latest();
        }]);
    }

    /**
     * Get total comments count (including replies)
     */
    public function getTotalCommentsAttribute(): int
    {
        return $this->comments()->count();
    }

    /**
     * Get main comments count (excluding replies)
     */
    public function getMainCommentsCountAttribute(): int
    {
        return $this->mainComments()->count();
    }
}
