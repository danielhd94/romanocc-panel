<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Law;
use App\Models\Article;
use App\Models\ArticleOpinion;
use App\Models\ArticleResolution;
use App\Models\ArticleVideo;
use App\Services\LawStructureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LawController extends Controller
{
    protected LawStructureService $lawStructureService;

    public function __construct(LawStructureService $lawStructureService)
    {
        $this->lawStructureService = $lawStructureService;
    }

    /**
     * GET /api/v2/laws
     * Retorna la estructura jerárquica paginada para la app móvil
     */
    public function index(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $perPage = $request->get('per_page', 5); // Cargar 5 títulos por página
        $type = $request->get('type'); // Filtrar por tipo: 'ley' o 'reglamento'
        
        // Determinar si es carga inicial (solo nombres) o búsqueda (con contenido)
        $isSearchMode = $request->has('search') && !empty($request->get('search'));
        
        $query = Law::with([
            'titles' => function ($q) {
                $q->with([
                    'articles' => function ($qa) { 
                        $qa->orderBy('article_number', 'asc');
                    },
                    'chapters' => function ($qc) {
                        $qc->with([
                            'articles' => function ($qa) { 
                                $qa->orderBy('article_number', 'asc');
                            },
                            'subchapters.articles' => function ($qs) { 
                                $qs->orderBy('article_number', 'asc');
                            },
                        ])->orderBy('id', 'asc');
                    }
                ])->orderBy('id', 'asc');
            },
        ]);
        
        // Aplicar filtro por tipo si se especifica
        if ($type) {
            $query->where('type', $type);
        }
        
        $laws = $query->get();

        // Transformar a la estructura esperada por la app móvil usando el servicio
        $allFormattedData = $laws->flatMap(function ($law) use ($isSearchMode) {
            return $law->titles->map(function ($title) use ($law, $isSearchMode) {
                $formattedTitle = $this->lawStructureService->formatTitleWithChapters($title, $isSearchMode);
                $formattedTitle['law_id'] = $law->id;
                return $formattedTitle;
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
     * GET /api/v2/laws/{id}
     * Retorna una ley específica con estructura jerárquica
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $law = Law::with([
            'titles' => function ($q) {
                $q->with([
                    'articles' => function ($qa) { 
                        $qa->orderBy('article_number', 'asc'); 
                    },
                    'chapters' => function ($qc) {
                        $qc->with([
                            'articles' => function ($qa) { 
                                $qa->orderBy('article_number', 'asc'); 
                            },
                            'subchapters.articles' => function ($qs) { 
                                $qs->orderBy('article_number', 'asc'); 
                            },
                        ])->orderBy('id', 'asc');
                    }
                ])->orderBy('id', 'asc');
            },
        ])->find($id);

        if (!$law) {
            return response()->json([
                'success' => false, 
                'message' => 'Ley no encontrada'
            ], 404);
        }

        // Transformar a la estructura esperada por la app móvil usando el servicio
        $formattedData = $law->titles->map(function ($title) {
            if ($title->chapters->isNotEmpty()) {
                $chapters = $title->chapters->map(function ($chapter) {
                    return $this->lawStructureService->formatChapterForShow($chapter);
                });
            } else {
                $chapters = $this->lawStructureService->createVirtualChapterForShow($title);
            }

            return [
                'title' => (string) $title->title,
                'chapters' => $chapters->values(),
            ];
        })->values();

        return response()->json([
            'success' => true, 
            'data' => $formattedData
        ], 200);
    }

    /**
     * GET /api/v2/laws/{id}/detail
     * Retorna información plana de la ley con opiniones, resoluciones, videos y adiciones
     */
    public function detail(Request $request, int $id): JsonResponse
    {
        $law = Law::with([
            'titles.chapters.articles.opinions',
            'titles.chapters.articles.resolutions',
            'titles.chapters.articles.videos',
            'titles.chapters.subchapters.articles.opinions',
            'titles.chapters.subchapters.articles.resolutions',
            'titles.chapters.subchapters.articles.videos'
        ])->find($id);

        if (!$law) {
            return response()->json([
                'success' => false, 
                'message' => 'Ley no encontrada'
            ], 404);
        }

        // Recopilar todos los artículos con sus datos relacionados
        $articlesData = collect();

        foreach ($law->titles as $title) {
            foreach ($title->chapters as $chapter) {
                // Artículos directos del capítulo
                foreach ($chapter->articles as $article) {
                    $articlesData->push([
                        'id' => $article->id,
                        'law_id' => $law->id,
                        'law_name' => $law->name,
                        'title' => $article->article_title,
                        'content' => $article->article_content,
                        'number' => $article->article_number,
                        'chapter' => $chapter->chapter_title ?: 'CAPÍTULO ' . $chapter->chapter_number,
                        'subchapter' => null,
                        'opinions' => $article->opinions->map(function ($opinion) {
                            return [
                                'id' => $opinion->id,
                                'opinion' => $opinion->opinion,
                                'url_file' => $opinion->url_file,
                                'user_name' => $opinion->user ? $opinion->user->name : 'Usuario',
                                'created_at' => $opinion->created_at,
                                'updated_at' => $opinion->updated_at,
                            ];
                        }),
                        'resolutions' => $article->resolutions->map(function ($resolution) {
                            return [
                                'id' => $resolution->id,
                                'name' => $resolution->name,
                                'url' => $resolution->url,
                                'url_pdf' => $resolution->url_pdf,
                                'user_name' => $resolution->user ? $resolution->user->name : 'Usuario',
                                'created_at' => $resolution->created_at,
                                'updated_at' => $resolution->updated_at,
                            ];
                        }),
                        'videos' => $article->videos->map(function ($video) {
                            return [
                                'id' => $video->id,
                                'name' => $video->name,
                                'url' => $video->url,
                                'user_name' => $video->user ? $video->user->name : 'Usuario',
                                'created_at' => $video->created_at,
                                'updated_at' => $video->updated_at,
                            ];
                        }),
                        'created_at' => $article->created_at,
                        'updated_at' => $article->updated_at,
                    ]);
                }

                // Artículos de subcapítulos
                foreach ($chapter->subchapters as $subchapter) {
                    foreach ($subchapter->articles as $article) {
                        $articlesData->push([
                            'id' => $article->id,
                            'law_id' => $law->id,
                            'law_name' => $law->name,
                            'title' => $article->article_title,
                            'content' => $article->article_content,
                            'number' => $article->article_number,
                            'chapter' => $chapter->chapter_title ?: 'CAPÍTULO ' . $chapter->chapter_number,
                            'subchapter' => $subchapter->subchapter_title,
                            'opinions' => $article->opinions->map(function ($opinion) {
                                return [
                                    'id' => $opinion->id,
                                    'opinion' => $opinion->opinion,
                                    'url_file' => $opinion->url_file,
                                    'user_name' => $opinion->user ? $opinion->user->name : 'Usuario',
                                    'created_at' => $opinion->created_at,
                                    'updated_at' => $opinion->updated_at,
                                ];
                            }),
                            'resolutions' => $article->resolutions->map(function ($resolution) {
                                return [
                                    'id' => $resolution->id,
                                    'name' => $resolution->name,
                                    'url' => $resolution->url,
                                    'url_pdf' => $resolution->url_pdf,
                                    'user_name' => $resolution->user ? $resolution->user->name : 'Usuario',
                                    'created_at' => $resolution->created_at,
                                    'updated_at' => $resolution->updated_at,
                                ];
                            }),
                            'videos' => $article->videos->map(function ($video) {
                                return [
                                    'id' => $video->id,
                                    'name' => $video->name,
                                    'url' => $video->url,
                                    'user_name' => $video->user ? $video->user->name : 'Usuario',
                                    'created_at' => $video->created_at,
                                    'updated_at' => $video->updated_at,
                                ];
                            }),
                            'created_at' => $article->created_at,
                            'updated_at' => $article->updated_at,
                        ]);
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $law->id,
                'title' => $law->name,
                'description' => 'Ley General de Contrataciones Públicas',
                'category' => 'ley',
                'articles' => $articlesData->values(),
                'created_at' => $law->created_at,
                'updated_at' => $law->updated_at,
            ]
        ], 200);
    }
}
