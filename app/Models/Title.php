<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Law;
use App\Models\Chapter;

class Title extends Model
{
    protected $fillable = ['law_id', 'title'];

    public function law()
    {
        return $this->belongsTo(Law::class, 'law_id', 'id');
    }

    public function chapters()
    {
        return $this->hasMany(Chapter::class, 'title_id', 'id');
    }
}
