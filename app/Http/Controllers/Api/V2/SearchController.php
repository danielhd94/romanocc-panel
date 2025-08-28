<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\Law;
use App\Models\Regulation;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    /**
     * Buscar en leyes y reglamentos
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:1',
            'type' => 'nullable|string|in:ley,reglamento,ambos',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
        ]);

        $query = $request->input('query');
        $type = $request->input('type', 'ambos');
        $page = $request->input('page', 1);
        $perPage = $request->input('per_page', 10);

        $results = collect();

        // Buscar en leyes (que incluye tanto leyes como reglamentos)
        if ($type === 'ley' || $type === 'reglamento' || $type === 'ambos') {
                           $laws = Law::with([
                   'titles.chapters' => function ($q) use ($query) {
                       $q->with([
                           'articles' => function ($qa) use ($query) {
                               $qa->where(function ($q) use ($query) {
                                   $q->where('article_title', 'like', "%{$query}%")
                                     ->orWhere('article_content', 'like', "%{$query}%");
                               });
                           },
                           'subchapters.articles' => function ($qs) use ($query) {
                               $qs->where(function ($q) use ($query) {
                                   $q->where('article_title', 'like', "%{$query}%")
                                     ->orWhere('article_content', 'like', "%{$query}%");
                               });
                           },
                       ]);
                   },
               ])
               ->whereHas('titles.chapters.articles', function ($q) use ($query) {
                   $q->where('article_title', 'like', "%{$query}%")
                     ->orWhere('article_content', 'like', "%{$query}%");
               })
               ->orWhereHas('titles.chapters.subchapters.articles', function ($q) use ($query) {
                   $q->where('article_title', 'like', "%{$query}%")
                     ->orWhere('article_content', 'like', "%{$query}%");
               })
               ->get()
               ->flatMap(function ($law) use ($type, $query) {
                   return $this->formatLawForSearch($law, $type, $query);
               });

               $results = $results->merge($laws);
        }

        // Aplicar paginación
        $totalItems = $results->count();
        $totalPages = ceil($totalItems / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginatedResults = $results->slice($offset, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginatedResults->values(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_next_page' => $page < $totalPages,
                'has_previous_page' => $page > 1,
            ],
            'search_info' => [
                'query' => $query,
                'type' => $type,
                'total_results' => $totalItems,
            ]
        ]);
    }

    /**
     * Formatear ley para búsqueda
     */
    private function formatLawForSearch($law, $type, $query)
    {
        $formattedTitles = [];

        // Mantener la estructura jerárquica original
        foreach ($law->titles as $title) {
            $formattedChapters = [];
            
            foreach ($title->chapters as $chapter) {
                $chapterArticles = collect();
                
                // Artículos directos del capítulo
                foreach ($chapter->articles as $article) {
                    $contentFragment = $this->extractTextFragment($article->article_content, $query);
                    if (!empty($contentFragment)) {
                        $chapterArticles->push([
                            'number' => $article->article_number,
                            'title' => $article->article_title,
                            'content' => $article->article_content,
                            'content_fragment' => $contentFragment,
                        ]);
                    }
                }

                // Artículos de subcapítulos
                foreach ($chapter->subchapters as $subchapter) {
                    foreach ($subchapter->articles as $article) {
                        $contentFragment = $this->extractTextFragment($article->article_content, $query);
                        if (!empty($contentFragment)) {
                            $chapterArticles->push([
                                'number' => $article->article_number,
                                'title' => $article->article_title,
                                'content' => $article->article_content,
                                'content_fragment' => $contentFragment,
                            ]);
                        }
                    }
                }

                // Solo incluir capítulos que tengan artículos con coincidencias
                if ($chapterArticles->count() > 0) {
                    $formattedChapters[] = [
                        'chapter' => $chapter->chapter_title ?: "CAPÍTULO " . $chapter->chapter_number,
                        'articles' => $chapterArticles->sortBy('number')->values()->toArray(),
                    ];
                }
            }

            // Solo incluir títulos que tengan capítulos con artículos
            if (count($formattedChapters) > 0) {
                $formattedTitles[] = [
                    'id' => $law->id,
                    'title' => (string) $title->title,
                    'type' => $type,
                    'chapters' => $formattedChapters,
                ];
            }
        }

        // Devolver múltiples estructuras jerárquicas (una por cada título)
        return $formattedTitles;
    }

    /**
     * Extraer fragmento de texto que contiene la búsqueda
     */
    private function extractTextFragment($content, $query, $maxWords = 30)
    {
        $lowerContent = strtolower($content);
        $lowerQuery = strtolower($query);
        
        $position = strpos($lowerContent, $lowerQuery);
        
        if ($position === false) {
            return '';
        }
        
        // Calcular posición de inicio y fin del fragmento (aumentado para más contexto)
        $contextSize = 150; // Caracteres antes y después (aumentado para más contexto)
        $start = max(0, $position - $contextSize);
        $end = min(strlen($content), $position + strlen($query) + $contextSize);
        
        $fragment = substr($content, $start, $end - $start);
        
        // Asegurar que el fragmento comience y termine en palabras completas
        $words = explode(' ', $fragment);
        if (count($words) > $maxWords) {
            $words = array_slice($words, 0, $maxWords);
        }
        
        $fragment = implode(' ', $words);
        
        // Agregar puntos suspensivos si es necesario
        if ($start > 0) {
            $fragment = '...' . $fragment;
        }
        if ($end < strlen($content)) {
            $fragment = $fragment . '...';
        }
        
        return trim($fragment);
    }
}
