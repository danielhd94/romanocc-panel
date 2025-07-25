<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InformationApp extends Model
{
    protected $fillable = [
        'url_terminos_y_condiciones',
        'url_politica_de_privacidad',
    ];
}
