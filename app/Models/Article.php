<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Law;

class Article extends Model
{
    protected $fillable = ['law_id', 'title_id', 'chapter_id', 'subchapter_id', 'article_number', 'article_title', 'article_content'];

    public function law()
    {
        return $this->belongsTo(Law::class, 'law_id', 'id');
    }

    public function title()
    {
        return $this->belongsTo(Title::class, 'title_id', 'id');
    }

    public function chapter()
    {
        return $this->belongsTo(Chapter::class, 'chapter_id', 'id');
    }

    public function subchapter()
    {
        return $this->belongsTo(Subchapter::class, 'subchapter_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(ArticleComment::class, 'article_id', 'id');
    }

    public function opinions()
    {
        return $this->hasMany(ArticleOpinion::class, 'article_id', 'id');
    }

    public function resolutions()
    {
        return $this->hasMany(ArticleResolution::class, 'article_id', 'id');
    }

    public function videos()
    {
        return $this->hasMany(ArticleVideo::class, 'article_id', 'id');
    }

    /**
     * Relación con archivos del artículo
     * Un artículo puede tener múltiples archivos
     */
    public function files()
    {
        return $this->hasMany(ArticleFile::class, 'article_id', 'id');
    }
}
