<?php

declare(strict_types=1);

namespace Api\Utils;

class Permission
{
    // Deve ser idêntico ao valor do ENUM mapeado na tabela do banco
    public const ADMIN = 'administrador'; 
    public const JEFE = 'jefe_proyecto';
    public const COLABORADOR = 'colaborador';

    public static function canManageUsers(string $role): bool
    {
        return $role === self::ADMIN;
    }

    public static function canCreateProject(string $role): bool
    {
        return in_array($role, [self::ADMIN, self::JEFE], true);
    }

    public static function canEditProject(string $role, bool $isResponsible): bool
    {
        return $role === self::ADMIN || ($role === self::JEFE && $isResponsible);
    }

    public static function canViewProject(string $role, bool $isMember): bool
    {
        return $role === self::ADMIN || $role === self::JEFE || $isMember;
    }

    public static function canManageMembers(string $role): bool
    {
        return in_array($role, [self::ADMIN, self::JEFE], true);
    }

    /**
     * Validação centralizada para manipulação de fases
     */
    public static function canEditPhase(string $role, bool $isResponsible, bool $isMember): bool
    {
        if ($role === self::ADMIN) {
            return true;
        }

        if ($role === self::JEFE) {
            return $isResponsible;
        }

        if ($role === self::COLABORADOR) {
            return $isMember;
        }

        return false;
    }
}