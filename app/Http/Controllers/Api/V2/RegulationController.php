<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Law;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegulationController extends Controller
{
    /**
     * GET /api/v2/regulations
     * Retorna la estructura jerárquica paginada para la app móvil
     * Nota: Usamos el mismo modelo Law pero filtramos por tipo 'reglamento'
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5); // Cargar 5 títulos por página
        
        $regulations = Law::where('type', 'reglamento')
            ->with([
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
        $allFormattedData = $regulations->flatMap(function ($regulation) {
            return $regulation->titles->map(function ($title) {
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

        // Aplicar paginación
        $totalItems = $allFormattedData->count();
        $offset = ($page - 1) * $perPage;
        $paginatedData = $allFormattedData->slice($offset, $perPage)->values();

        return response()->json([
            'success' => true,
            'data' => $paginatedData,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => ceil($totalItems / $perPage),
                'has_next_page' => ($page * $perPage) < $totalItems,
                'has_previous_page' => $page > 1
            ]
        ], 200);
    }

    /**
     * GET /api/v2/regulations/{id}
     * Retorna un reglamento específico con estructura jerárquica
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $regulation = Law::where('type', 'reglamento')
            ->with([
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

        if (!$regulation) {
            return response()->json([
                'success' => false, 
                'message' => 'Reglamento no encontrado'
            ], 404);
        }

        // Transformar a la estructura esperada por la app móvil
        $formattedData = $regulation->titles->map(function ($title) {
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
     * GET /api/v2/regulations/{id}/detail
     * Retorna información plana del reglamento (para servicios)
     */
    public function detail(Request $request, int $id): JsonResponse
    {
        $regulation = Law::where('type', 'reglamento')->find($id);

        if (!$regulation) {
            return response()->json([
                'success' => false, 
                'message' => 'Reglamento no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $regulation->id,
                'title' => $regulation->name,
                'description' => 'Reglamento de la Ley General de Contrataciones Públicas',
                'content' => 'Contenido completo del reglamento...',
                'category' => 'reglamento',
                'file_url' => null,
                'created_at' => $regulation->created_at,
                'updated_at' => $regulation->updated_at,
            ]
        ], 200);
    }
}
