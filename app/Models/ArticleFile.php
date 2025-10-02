<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArticleFile extends Model
{
    /**
     * Campos que se pueden asignar masivamente
     */
    protected $fillable = [
        'article_id',
        'file_path'
    ];

    /**
     * Relación con el modelo Article
     * Un archivo pertenece a un artículo
     */
    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }

    /**
     * Obtiene la URL completa del archivo
     */
    public function getFileUrlAttribute()
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Obtiene el nombre del archivo desde la ruta
     */
    public function getFileNameAttribute()
    {
        return basename($this->file_path);
    }
}
