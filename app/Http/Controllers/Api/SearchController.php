<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Law;
use App\Models\Title;
use App\Models\Chapter;
use App\Models\Subchapter;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Search in laws, titles, chapters, subchapters and articles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ], [
            'query.required' => 'El término de búsqueda es requerido.',
            'query.min' => 'El término de búsqueda debe tener al menos 2 caracteres.',
        ]);

        $query = $request->get('query');
        $searchTerm = '%' . $query . '%';

        // Search in laws
        $laws = Law::where('name', 'like', $searchTerm)
            ->with(['titles' => function ($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                  ->with(['chapters' => function ($q) use ($searchTerm) {
                      $q->where('chapter', 'like', $searchTerm)
                        ->with(['subchapters' => function ($q) use ($searchTerm) {
                            $q->where('subchapter', 'like', $searchTerm)
                              ->with(['articles' => function ($q) use ($searchTerm) {
                                  $q->where('content', 'like', $searchTerm);
                              }]);
                        }]);
                  }]);
            }])
            ->get();

        // Search in titles
        $titles = Title::where('title', 'like', $searchTerm)
            ->with(['law', 'chapters' => function ($q) use ($searchTerm) {
                $q->where('chapter', 'like', $searchTerm)
                  ->with(['subchapters' => function ($q) use ($searchTerm) {
                      $q->where('subchapter', 'like', $searchTerm)
                        ->with(['articles' => function ($q) use ($searchTerm) {
                            $q->where('content', 'like', $searchTerm);
                        }]);
                  }]);
            }])
            ->get();

        // Search in chapters
        $chapters = Chapter::where('chapter', 'like', $searchTerm)
            ->with(['title.law', 'subchapters' => function ($q) use ($searchTerm) {
                $q->where('subchapter', 'like', $searchTerm)
                  ->with(['articles' => function ($q) use ($searchTerm) {
                      $q->where('content', 'like', $searchTerm);
                  }]);
            }])
            ->get();

        // Search in subchapters
        $subchapters = Subchapter::where('subchapter', 'like', $searchTerm)
            ->with(['chapter.title.law', 'articles' => function ($q) use ($searchTerm) {
                $q->where('content', 'like', $searchTerm);
            }])
            ->get();

        // Search in articles
        $articles = Article::where('content', 'like', $searchTerm)
            ->with(['subchapter.chapter.title.law'])
            ->get();

        $results = [
            'laws' => $laws->map(function ($law) {
                return [
                    'type' => 'law',
                    'id' => $law->id,
                    'name' => $law->name,
                    'matched_content' => $law->name,
                    'titles' => $law->titles->map(function ($title) {
                        return [
                            'id' => $title->id,
                            'title' => $title->title,
                            'matched_content' => $title->title,
                        ];
                    }),
                ];
            }),
            'titles' => $titles->map(function ($title) {
                return [
                    'type' => 'title',
                    'id' => $title->id,
                    'title' => $title->title,
                    'law_name' => $title->law->name,
                    'law_id' => $title->law->id,
                    'matched_content' => $title->title,
                ];
            }),
            'chapters' => $chapters->map(function ($chapter) {
                return [
                    'type' => 'chapter',
                    'id' => $chapter->id,
                    'chapter' => $chapter->chapter,
                    'title' => $chapter->title->title,
                    'law_name' => $chapter->title->law->name,
                    'law_id' => $chapter->title->law->id,
                    'matched_content' => $chapter->chapter,
                ];
            }),
            'subchapters' => $subchapters->map(function ($subchapter) {
                return [
                    'type' => 'subchapter',
                    'id' => $subchapter->id,
                    'subchapter' => $subchapter->subchapter,
                    'chapter' => $subchapter->chapter->chapter,
                    'title' => $subchapter->chapter->title->title,
                    'law_name' => $subchapter->chapter->title->law->name,
                    'law_id' => $subchapter->chapter->title->law->id,
                    'matched_content' => $subchapter->subchapter,
                ];
            }),
            'articles' => $articles->map(function ($article) {
                return [
                    'type' => 'article',
                    'id' => $article->id,
                    'number' => $article->number,
                    'content' => $article->content,
                    'subchapter' => $article->subchapter->subchapter,
                    'chapter' => $article->subchapter->chapter->chapter,
                    'title' => $article->subchapter->chapter->title->title,
                    'law_name' => $article->subchapter->chapter->title->law->name,
                    'law_id' => $article->subchapter->chapter->title->law->id,
                    'matched_content' => $article->content,
                ];
            }),
        ];

        $totalResults = count($results['laws']) + count($results['titles']) + 
                       count($results['chapters']) + count($results['subchapters']) + 
                       count($results['articles']);

        return response()->json([
            'success' => true,
            'data' => [
                'query' => $query,
                'total_results' => $totalResults,
                'results' => $results,
            ],
        ], 200);
    }
}
