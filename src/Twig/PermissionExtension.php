<?php

namespace App\Twig;

use App\Service\PermissionService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PermissionExtension extends AbstractExtension
{
    private PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getUserPermissions', [$this, 'getUserPermissions']),
        ];
    }

    public function getUserPermissions(): array
    {
        return [
            'canViewUsers' => $this->permissionService->canPerform('view', 'user'),
            'canEditUsers' => $this->permissionService->canPerform('edit', 'user'),
            'canViewProjects' => $this->permissionService->canPerform('view', 'project'),
            'canEditProjects' => $this->permissionService->canPerform('edit', 'project'),
            'canViewCustomers' => $this->permissionService->canPerform('view', 'customer'),
            'canEditCustomers' => $this->permissionService->canPerform('edit', 'customer'),
            'canViewParameters' => $this->permissionService->canPerform('view', 'parameter'),
            'canEditParameters' => $this->permissionService->canPerform('edit', 'parameter'),
            'canCreateProject' => $this->permissionService->canPerform('create', 'project'),
        ];
    }
} 