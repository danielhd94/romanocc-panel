<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Law;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LawController extends Controller
{
    /**
     * GET /api/v2/laws
     * Retorna la estructura jerárquica completa para la app móvil
     */
    public function index(Request $request): JsonResponse
    {
        $laws = Law::with([
            'titles.chapters' => function ($q) {
                $q->with([
                    'articles' => function ($qa) { 
                        $qa->orderBy('article_number', 'asc'); 
                    },
                    'subchapters.articles' => function ($qs) { 
                        $qs->orderBy('article_number', 'asc'); 
                    },
                ])->orderBy('id', 'asc');
            },
        ])->get();

        // Transformar a la estructura esperada por la app móvil
        $formattedData = $laws->flatMap(function ($law) {
            return $law->titles->map(function ($title) {
                return [
                    'title' => (string) $title->title,
                    'chapters' => $title->chapters->map(function ($chapter) {
                        $articles = collect();

                        // Artículos directos del capítulo
                        $articles = $articles->merge($chapter->articles->map(function ($article) {
                            return [
                                'number' => $article->article_number,
                                'title' => $article->article_title,
                                'content' => $article->article_content,
                            ];
                        }));

                        // Artículos de subcapítulos
                        foreach ($chapter->subchapters as $subchapter) {
                            foreach ($subchapter->articles as $article) {
                                $articles->push([
                                    'number' => $article->article_number,
                                    'title' => $article->article_title,
                                    'content' => $article->article_content,
                                ]);
                            }
                        }

                        // Ordenar por número
                        $articles = $articles->sortBy('number')->values();

                        return [
                            'chapter' => $chapter->chapter_title ?: "CAPÍTULO " . $chapter->chapter_number,
                            'articles' => $articles->toArray(),
                        ];
                    })->values(),
                ];
            });
        })->values();

        return response()->json([
            'success' => true,
            'data' => $formattedData
        ], 200);
    }

    /**
     * GET /api/v2/laws/{id}
     * Retorna una ley específica con estructura jerárquica
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $law = Law::with([
            'titles.chapters' => function ($q) {
                $q->with([
                    'articles' => function ($qa) { 
                        $qa->orderBy('article_number', 'asc'); 
                    },
                    'subchapters.articles' => function ($qs) { 
                        $qs->orderBy('article_number', 'asc'); 
                    },
                ])->orderBy('id', 'asc');
            },
        ])->find($id);

        if (!$law) {
            return response()->json([
                'success' => false, 
                'message' => 'Ley no encontrada'
            ], 404);
        }

        // Transformar a la estructura esperada por la app móvil
        $formattedData = $law->titles->map(function ($title) {
            return [
                'title' => (string) $title->title,
                'chapters' => $title->chapters->map(function ($chapter) {
                    $articles = collect();

                    // Artículos directos del capítulo
                    $articles = $articles->merge($chapter->articles->map(function ($article) {
                        return [
                            'number' => $article->article_number,
                            'title' => $article->article_title,
                            'content' => $article->article_content,
                        ];
                    }));

                    // Artículos de subcapítulos
                    foreach ($chapter->subchapters as $subchapter) {
                        foreach ($subchapter->articles as $article) {
                            $articles->push([
                                'number' => $article->article_number,
                                'title' => $article->article_title,
                                'content' => $article->article_content,
                            ]);
                        }
                    }

                    // Ordenar por número
                    $articles = $articles->sortBy('number')->values();

                    return [
                        'chapter' => $chapter->chapter_title ?: "CAPÍTULO " . $chapter->chapter_number,
                        'articles' => $articles->toArray(),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'success' => true, 
            'data' => $formattedData
        ], 200);
    }

    /**
     * GET /api/v2/laws/{id}/detail
     * Retorna información plana de la ley (para servicios)
     */
    public function detail(Request $request, int $id): JsonResponse
    {
        $law = Law::find($id);

        if (!$law) {
            return response()->json([
                'success' => false, 
                'message' => 'Ley no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $law->id,
                'title' => $law->name,
                'description' => 'Ley General de Contrataciones Públicas',
                'content' => 'Contenido completo de la ley...',
                'category' => 'ley',
                'file_url' => null,
                'created_at' => $law->created_at,
                'updated_at' => $law->updated_at,
            ]
        ], 200);
    }
}
