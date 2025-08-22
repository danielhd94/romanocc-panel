<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ForumTopic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ForumController extends Controller
{
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
                'title' => 'required|string|max:255',
                'content' => 'required|string',
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
                'title' => 'required|string|max:255',
                'content' => 'required|string',
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
     * Get comments for a topic (opcional, ya que usamos Firebase)
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

            // Por ahora retornamos un array vacío ya que los comentarios se manejan en Firebase
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'Comentarios obtenidos exitosamente (manejados por Firebase)'
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
     * Create a comment for a topic (opcional, ya que usamos Firebase)
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
                'content' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Por ahora solo incrementamos el contador de comentarios
            $topic->increment('comments_count');

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => 'firebase_comment_id',
                    'content' => $request->content,
                    'user_id' => auth()->id(),
                    'user_name' => auth()->user()->name,
                    'topic_id' => $topicId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                'message' => 'Comentario creado exitosamente (manejado por Firebase)'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el comentario',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
