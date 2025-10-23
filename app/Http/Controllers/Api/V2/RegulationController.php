<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Law;
use App\Services\LawStructureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegulationController extends Controller
{
    protected LawStructureService $lawStructureService;

    public function __construct(LawStructureService $lawStructureService)
    {
        $this->lawStructureService = $lawStructureService;
    }

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
                'titles' => function ($q) {
                    $q->with([
                        'articles' => function ($qa) { 
                            $qa->orderByRaw('LPAD(article_number, 10, "0") ASC');
                        },
                        'chapters' => function ($qc) {
                            $qc->with([
                                'articles' => function ($qa) { 
                                    $qa->orderByRaw('LPAD(article_number, 10, "0") ASC'); 
                                },
                                'subchapters' => function ($qs) {
                                    $qs->with(['articles' => function ($qsa) {
                                        $qsa->orderByRaw('LPAD(article_number, 10, "0") ASC');
                                    }])->orderByRaw('LPAD(subchapter_number, 10, "0") ASC');
                                },
                            ])->orderByRaw('LPAD(chapter_number, 10, "0") ASC');
                        }
                    ])->orderBy('title', 'asc');
                },
            ])->orderBy('id', 'asc')->get();

        // Transformar a la estructura esperada por la app móvil usando el servicio
        $allFormattedData = $regulations->flatMap(function ($regulation) {
            return $regulation->titles->map(function ($title) use ($regulation) {
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
                    'law_id' => $regulation->id,
                    'law_type' => $regulation->type,
                    'law_name' => $regulation->name,
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
                'titles' => function ($q) {
                    $q->with([
                        'articles' => function ($qa) { 
                            $qa->orderByRaw('LPAD(article_number, 10, "0") ASC');
                        },
                        'chapters' => function ($qc) {
                            $qc->with([
                                'articles' => function ($qa) { 
                                    $qa->orderByRaw('LPAD(article_number, 10, "0") ASC'); 
                                },
                                'subchapters' => function ($qs) {
                                    $qs->with(['articles' => function ($qsa) {
                                        $qsa->orderByRaw('LPAD(article_number, 10, "0") ASC');
                                    }])->orderByRaw('LPAD(subchapter_number, 10, "0") ASC');
                                },
                            ])->orderByRaw('LPAD(chapter_number, 10, "0") ASC');
                        }
                    ])->orderBy('title', 'asc');
                },
            ])->orderBy('id', 'asc')->find($id);

        if (!$regulation) {
            return response()->json([
                'success' => false, 
                'message' => 'Reglamento no encontrado'
            ], 404);
        }

        // Transformar a la estructura esperada por la app móvil usando el servicio
        $formattedData = $regulation->titles->map(function ($title) use ($regulation) {
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
                'law_id' => $regulation->id,
                'law_type' => $regulation->type,
                'law_name' => $regulation->name,
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
        $regulation = Law::where('type', 'reglamento')->orderBy('id', 'asc')->find($id);

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
