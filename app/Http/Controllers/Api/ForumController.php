<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumTopic;
use App\Models\Comment;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ForumController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get all forum topics
     */
    public function getTopics(): JsonResponse
    {
        try {
            $topics = ForumTopic::withUser()
                ->latest()
                ->get()
                ->map(function ($topic) {
                    return [
                        'id' => $topic->id,
                        'title' => $topic->title,
                        'content' => $topic->content,
                        'user_id' => $topic->user_id,
                        'user_name' => $topic->user->name,
                        'comments_count' => $topic->comments_count,
                        'created_at' => $topic->created_at,
                        'updated_at' => $topic->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $topics,
                'message' => 'Temas obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los temas del foro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get topics of the authenticated user
     */
    public function getMyTopics(): JsonResponse
    {
        try {
            $topics = ForumTopic::withUser()
                ->where('user_id', auth()->id())
                ->latest()
                ->get()
                ->map(function ($topic) {
                    return [
                        'id' => $topic->id,
                        'title' => $topic->title,
                        'content' => $topic->content,
                        'user_id' => $topic->user_id,
                        'user_name' => $topic->user->name,
                        'comments_count' => $topic->comments_count,
                        'created_at' => $topic->created_at,
                        'updated_at' => $topic->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $topics,
                'message' => 'Tus temas obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tus temas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific forum topic
     */
    public function getTopicDetail($id): JsonResponse
    {
        try {
            $topic = ForumTopic::withUser()->find($id);

            if (!$topic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tema no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'content' => $topic->content,
                    'user_id' => $topic->user_id,
                    'user_name' => $topic->user->name,
                    'comments_count' => $topic->comments_count,
                    'created_at' => $topic->created_at,
                    'updated_at' => $topic->updated_at,
                ],
                'message' => 'Tema obtenido exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tema',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new forum topic
     */
    public function createTopic(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:100',
                'content' => 'required|string|max:65535',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $topic = ForumTopic::create([
                'title' => $request->title,
                'content' => $request->content,
                'user_id' => auth()->id(),
            ]);

            $topic->load('user');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'content' => $topic->content,
                    'user_id' => $topic->user_id,
                    'user_name' => $topic->user->name,
                    'comments_count' => $topic->comments_count,
                    'created_at' => $topic->created_at,
                    'updated_at' => $topic->updated_at,
                ],
                'message' => 'Tema creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tema',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a forum topic
     */
    public function updateTopic(Request $request, $id): JsonResponse
    {
        try {
            $topic = ForumTopic::find($id);

            if (!$topic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tema no encontrado'
                ], 404);
            }

            // Verificar que el usuario sea el propietario del tema
            if ($topic->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este tema'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:100',
                'content' => 'required|string|max:65535',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $topic->update([
                'title' => $request->title,
                'content' => $request->content,
            ]);

            $topic->load('user');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $topic->id,
                    'title' => $topic->title,
                    'content' => $topic->content,
                    'user_id' => $topic->user_id,
                    'user_name' => $topic->user->name,
                    'comments_count' => $topic->comments_count,
                    'created_at' => $topic->created_at,
                    'updated_at' => $topic->updated_at,
                ],
                'message' => 'Tema actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tema',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a forum topic
     */
    public function deleteTopic($id): JsonResponse
    {
        try {
            $topic = ForumTopic::find($id);

            if (!$topic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tema no encontrado'
                ], 404);
            }

            // Verificar que el usuario sea el propietario del tema
            if ($topic->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este tema'
                ], 403);
            }

            $topic->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tema eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tema',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get comments for a topic
     */
    public function getComments($topicId): JsonResponse
    {
        try {
            $topic = ForumTopic::find($topicId);

            if (!$topic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tema no encontrado'
                ], 404);
            }

            // Obtener comentarios principales con sus respuestas
            $mainComments = $topic->mainComments()
                ->with(['user', 'replies.user'])
                ->latest()
                ->get();

            $commentsData = $mainComments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user_id' => $comment->user_id,
                    'user_name' => $comment->user->name,
                    'topic_id' => $comment->commentable_id,
                    'parent_id' => $comment->parent_id,
                    'is_reply' => $comment->isReply(),
                    'has_replies' => $comment->hasReplies(),
                    'replies_count' => $comment->repliesCount(),
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                    'replies' => $comment->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'content' => $reply->content,
                            'user_id' => $reply->user_id,
                            'user_name' => $reply->user->name,
                            'parent_id' => $reply->parent_id,
                            'is_reply' => $reply->isReply(),
                            'has_replies' => $reply->hasReplies(),
                            'replies_count' => $reply->repliesCount(),
                            'created_at' => $reply->created_at,
                            'updated_at' => $reply->updated_at,
                        ];
                    })->sortBy('created_at')->values()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $commentsData,
                'total_comments' => $topic->total_comments,
                'main_comments_count' => $topic->main_comments_count,
                'message' => 'Comentarios obtenidos exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los comentarios',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a comment for a topic
     */
    public function createComment(Request $request, $topicId): JsonResponse
    {
        try {
            $topic = ForumTopic::find($topicId);

            if (!$topic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tema no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:65535',
                'parent_id' => 'nullable|integer|exists:comments,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Si es una respuesta, verificar que el comentario padre pertenezca al mismo tema
            if ($request->parent_id) {
                $parentComment = Comment::find($request->parent_id);
                if (!$parentComment) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Comentario padre no encontrado'
                    ], 404);
                }
                
                // Verificar que el comentario padre pertenezca al mismo tema
                if ($parentComment->commentable_type !== ForumTopic::class || 
                    $parentComment->commentable_id != $topicId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El comentario padre no pertenece a este tema'
                    ], 422);
                }
            }

            // Crear el comentario en la base de datos
            $comment = Comment::create([
                'content' => $request->content,
                'user_id' => auth()->id(),
                'commentable_type' => ForumTopic::class,
                'commentable_id' => $topic->id,
                'parent_id' => $request->parent_id,
            ]);

            // Cargar relaciones necesarias
            $comment->load(['user', 'replies.user']);

            // Incrementar el contador de comentarios del tema
            $topic->increment('comments_count');

            // Determinar si es un comentario principal o una respuesta
            $isReply = !is_null($request->parent_id);

            if ($isReply) {
                // Es una respuesta: notificar al autor del comentario padre
                $parentComment = Comment::find($request->parent_id);
                if ($parentComment && $parentComment->user_id !== auth()->id()) {
                    $this->notificationService->sendForumCommentReplyNotification(
                        $parentComment->user_id,
                        auth()->user()->name,
                        $topic->title,
                        $request->content,
                        $topic->id
                    );
                }
            } else {
                // Es un comentario principal: notificar al autor del tema
                if ($topic->user_id !== auth()->id()) {
                    $this->notificationService->sendForumTopicCommentNotification(
                        $topic->user_id,
                        auth()->user()->name,
                        $topic->title,
                        $request->content,
                        $topic->id
                    );
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user_id' => $comment->user_id,
                    'user_name' => $comment->user->name,
                    'topic_id' => $topic->id,
                    'parent_id' => $comment->parent_id,
                    'is_reply' => $comment->isReply(),
                    'has_replies' => $comment->hasReplies(),
                    'replies_count' => $comment->repliesCount(),
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                    'replies' => $comment->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'content' => $reply->content,
                            'user_id' => $reply->user_id,
                            'user_name' => $reply->user->name,
                            'parent_id' => $reply->parent_id,
                            'created_at' => $reply->created_at,
                            'updated_at' => $reply->updated_at,
                        ];
                    })
                ],
                'message' => $isReply ? 'Respuesta creada exitosamente' : 'Comentario creado exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el comentario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a comment
     */
    public function deleteComment($commentId): JsonResponse
    {
        try {
            $comment = Comment::find($commentId);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comentario no encontrado'
                ], 404);
            }

            // Verificar que el usuario sea el propietario del comentario
            if ($comment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para eliminar este comentario'
                ], 403);
            }

            // Obtener el topic antes de eliminar para actualizar el contador
            $topic = $comment->commentable;
            $repliesCount = $comment->replies()->count();

            // Eliminar el comentario (las respuestas se eliminarán en cascada por la BD)
            $comment->delete();

            // Actualizar el contador de comentarios del tema
            if ($topic) {
                // Restar 1 por el comentario eliminado + las respuestas eliminadas
                $topic->decrement('comments_count', 1 + $repliesCount);
            }

            return response()->json([
                'success' => true,
                'message' => 'Comentario eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el comentario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update a comment
     */
    public function updateComment(Request $request, $commentId): JsonResponse
    {
        try {
            $comment = Comment::find($commentId);

            if (!$comment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comentario no encontrado'
                ], 404);
            }

            // Verificar que el usuario sea el propietario del comentario
            if ($comment->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para editar este comentario'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'content' => 'required|string|max:65535',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $comment->update([
                'content' => $request->content,
            ]);

            $comment->load(['user', 'replies.user']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'user_id' => $comment->user_id,
                    'user_name' => $comment->user->name,
                    'topic_id' => $comment->commentable_id,
                    'parent_id' => $comment->parent_id,
                    'is_reply' => $comment->isReply(),
                    'has_replies' => $comment->hasReplies(),
                    'replies_count' => $comment->repliesCount(),
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                    'replies' => $comment->replies->map(function ($reply) {
                        return [
                            'id' => $reply->id,
                            'content' => $reply->content,
                            'user_id' => $reply->user_id,
                            'user_name' => $reply->user->name,
                            'created_at' => $reply->created_at,
                            'updated_at' => $reply->updated_at,
                        ];
                    })
                ],
                'message' => 'Comentario actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el comentario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send notification for forum comment reply (called from frontend when reply is created in Firebase)
     */
    public function sendReplyNotification(Request $request, $topicId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'comment_owner_id' => 'required|integer',
                'reply_text' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $topic = ForumTopic::find($topicId);

            if (!$topic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tema no encontrado'
                ], 404);
            }

            // Enviar notificación al autor del comentario original
            $this->notificationService->sendForumCommentReplyNotification(
                $request->comment_owner_id,
                auth()->user()->name,
                $topic->title,
                $request->reply_text,
                $topic->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Notificación de respuesta enviada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar notificación de respuesta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
