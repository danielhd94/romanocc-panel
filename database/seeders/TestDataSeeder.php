<?php

namespace Database\Seeders;

use App\Models\Law;
use App\Models\Title;
use App\Models\Chapter;
use App\Models\Subchapter;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario de prueba
        User::create([
            'name' => 'Usuario de Prueba',
            'phone' => '1234567890',
            'password' => Hash::make('password123'),
            'accepted_terms' => true,
            'type' => 'public',
            'status' => 'active',
        ]);

        // Crear leyes de prueba
        $codigoCivil = Law::create([
            'name' => 'Código Civil',
        ]);

        $codigoPenal = Law::create([
            'name' => 'Código Penal',
        ]);

        // Crear títulos para Código Civil
        $tituloPersonas = Title::create([
            'law_id' => $codigoCivil->id,
            'title' => 'Título I - De las Personas',
        ]);

        $tituloBienes = Title::create([
            'law_id' => $codigoCivil->id,
            'title' => 'Título II - De los Bienes',
        ]);

        // Crear títulos para Código Penal
        $tituloDelitos = Title::create([
            'law_id' => $codigoPenal->id,
            'title' => 'Título I - De los Delitos',
        ]);

        // Crear capítulos para Título I del Código Civil
        $capPersonasNaturales = Chapter::create([
            'title_id' => $tituloPersonas->id,
            'chapter' => 'Capítulo I - De las Personas Naturales',
        ]);

        $capPersonasJuridicas = Chapter::create([
            'title_id' => $tituloPersonas->id,
            'chapter' => 'Capítulo II - De las Personas Jurídicas',
        ]);

        // Crear subcapítulos
        $subcapPersonalidad = Subchapter::create([
            'chapter_id' => $capPersonasNaturales->id,
            'subchapter' => 'Sección I - De la Personalidad',
        ]);

        $subcapCapacidad = Subchapter::create([
            'chapter_id' => $capPersonasNaturales->id,
            'subchapter' => 'Sección II - De la Capacidad',
        ]);

        // Crear artículos
        Article::create([
            'subchapter_id' => $subcapPersonalidad->id,
            'number' => '1',
            'content' => 'La ley es obligatoria para todos los habitantes de la República, sin distinción de nacionalidad.',
        ]);

        Article::create([
            'subchapter_id' => $subcapPersonalidad->id,
            'number' => '2',
            'content' => 'La personalidad civil comienza con el nacimiento y termina con la muerte.',
        ]);

        Article::create([
            'subchapter_id' => $subcapCapacidad->id,
            'number' => '3',
            'content' => 'Son personas todos los individuos de la especie humana, cualquiera que sea su edad, sexo, estirpe o condición.',
        ]);

        Article::create([
            'subchapter_id' => $subcapCapacidad->id,
            'number' => '4',
            'content' => 'La capacidad de goce se adquiere por el nacimiento y se pierde por la muerte.',
        ]);

        // Crear capítulos para Título II del Código Civil
        $capClasificacionBienes = Chapter::create([
            'title_id' => $tituloBienes->id,
            'chapter' => 'Capítulo I - Clasificación de los Bienes',
        ]);

        $subcapBienesMuebles = Subchapter::create([
            'chapter_id' => $capClasificacionBienes->id,
            'subchapter' => 'Sección I - De los Bienes Muebles',
        ]);

        Article::create([
            'subchapter_id' => $subcapBienesMuebles->id,
            'number' => '5',
            'content' => 'Los bienes son muebles o inmuebles.',
        ]);

        Article::create([
            'subchapter_id' => $subcapBienesMuebles->id,
            'number' => '6',
            'content' => 'Son bienes muebles los que pueden transportarse de un lugar a otro.',
        ]);

        // Crear capítulos para Código Penal
        $capDelitosContraPersonas = Chapter::create([
            'title_id' => $tituloDelitos->id,
            'chapter' => 'Capítulo I - Delitos Contra las Personas',
        ]);

        $subcapHomicidio = Subchapter::create([
            'chapter_id' => $capDelitosContraPersonas->id,
            'subchapter' => 'Sección I - Del Homicidio',
        ]);

        Article::create([
            'subchapter_id' => $subcapHomicidio->id,
            'number' => '1',
            'content' => 'El que matare a otro será reprimido con reclusión o prisión de ocho a veinticinco años.',
        ]);

        Article::create([
            'subchapter_id' => $subcapHomicidio->id,
            'number' => '2',
            'content' => 'En la misma pena incurrirá el que causare a otro una lesión grave.',
        ]);
    }
}
