<?php

namespace App\Filament\Widgets;

use App\Models\Article;
use App\Models\ArticleVisit;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MostVisitedArticles extends ChartWidget
{
    protected static ?string $heading = 'Artículos Más Visitados';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $mostVisitedArticles = ArticleVisit::select(
                'article_id',
                DB::raw('COUNT(*) as visit_count')
            )
            ->with('article:id,article_title')
            ->groupBy('article_id')
            ->orderByDesc('visit_count')
            ->limit(10)
            ->get();

        $labels = $mostVisitedArticles->map(function ($visit) {
            return $visit->article ? $visit->article->article_title : 'Artículo Eliminado';
        })->toArray();

        $data = $mostVisitedArticles->pluck('visit_count')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Visitas',
                    'data' => $data,
                    'backgroundColor' => [
                        '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
                        '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6B7280'
                    ],
                    'borderColor' => [
                        '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
                        '#06B6D4', '#84CC16', '#F97316', '#EC4899', '#6B7280'
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            return context.label + ": " + context.parsed + " visitas";
                        }'
                    ]
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }
} 