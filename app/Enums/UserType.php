<?php

namespace App\Enums;

enum UserType: string
{
    case ADMIN = 'admin';
    case LAWYER = 'lawyer';
    case STUDENT = 'student';
    case PROFESSOR = 'professor';
    case RESEARCHER = 'researcher';
    case PUBLIC = 'public';

    public function label(): string
    {
        return match($this) {
            self::ADMIN => 'Administrador',
            self::LAWYER => 'Abogado',
            self::STUDENT => 'Estudiante',
            self::PROFESSOR => 'Profesor',
            self::RESEARCHER => 'Investigador',
            self::PUBLIC => 'Público',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::ADMIN => 'danger',
            self::LAWYER => 'primary',
            self::STUDENT => 'success',
            self::PROFESSOR => 'warning',
            self::RESEARCHER => 'info',
            self::PUBLIC => 'gray',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($case) => [
            $case->value => $case->label()
        ])->toArray();
    }
} 