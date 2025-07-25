<?php

namespace App\Filament\Resources\TitleResource\Pages;

use App\Filament\Resources\TitleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTitle extends EditRecord
{
    protected static string $resource = TitleResource::class;

    # renombrar el titulo de la pagina
    public function getTitle(): string
    { 
        return 'Editar Título';
    }

    # Redirigir a la lista de títulos
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    # Agregar un botón para regresar a la lista de títulos
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Regresar')
                ->url($this->getResource()::getUrl('index'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray'),
        ];
    }
}
