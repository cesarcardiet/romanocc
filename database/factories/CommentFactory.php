<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\ForumTopic;
use App\Models\Comment;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->paragraph(3),
            'user_id' => User::factory(),
            'commentable_type' => ForumTopic::class,
            'commentable_id' => ForumTopic::factory(),
            'parent_id' => null, // Por defecto es un comentario principal
        ];
    }

    /**
     * Create a reply to a comment
     */
    public function reply(): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => Comment::factory(),
            'commentable_id' => null, // Las respuestas heredan el commentable del padre
            'commentable_type' => null,
        ]);
    }

    /**
     * Create a comment for a specific forum topic
     */
    public function forForumTopic(ForumTopic $topic): static
    {
        return $this->state(fn (array $attributes) => [
            'commentable_type' => ForumTopic::class,
            'commentable_id' => $topic->id,
        ]);
    }

    /**
     * Create a comment by a specific user
     */
    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    /**
     * Create a reply to a specific comment
     */
    public function replyTo(Comment $comment): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_id' => $comment->id,
            'commentable_type' => $comment->commentable_type,
            'commentable_id' => $comment->commentable_id,
        ]);
    }
}
