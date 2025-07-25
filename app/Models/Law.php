<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Law extends Model
{
    protected $fillable = ['name'];

    public function titles()
    {
        return $this->hasMany(Title::class, 'law_id', 'id');
    }
}
