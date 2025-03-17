<?php
// tests/Unit/Service/PermissionServiceTest.php

namespace App\Tests\Unit\Service;

use App\Entity\User;
use App\Service\PermissionService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;

class PermissionServiceTest extends TestCase
{
    /**
     * @var Security&\PHPUnit\Framework\MockObject\MockObject
     */
    private $security;
    
    /**
     * @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;
    
    private $permissionService;
    
    /**
     * @var User&\PHPUnit\Framework\MockObject\MockObject
     */
    private $user;

    protected function setUp(): void
    {
        // Créer les mocks
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->user = $this->createMock(User::class);
            
        // Configurer le mock User pour retourner un identifiant
        $this->user->expects($this->any())
            ->method('getUserIdentifier')
            ->willReturn('test@example.com');
            
        // Créer le service avec les mocks
        $this->permissionService = new PermissionService($this->security, $this->logger);
    }

    public function testCanPerformOnUserAsAdmin(): void
    {
        // Configurer le mock Security pour simuler un utilisateur admin
        $this->security->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
            
        // Configurer isGranted pour retourner true pour ROLE_ADMIN et false pour les autres rôles
        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($role) {
                return $role === 'ROLE_ADMIN';
            });

        // Vérifier les permissions d'un admin
        // L'admin a tous les droits
        $this->assertTrue($this->permissionService->canPerform('view', 'user'));
        $this->assertTrue($this->permissionService->canPerform('edit', 'user'));
        $this->assertTrue($this->permissionService->canPerform('delete', 'user'));
        $this->assertTrue($this->permissionService->canPerform('edit', 'parameter'));
        
        // Vérifier d'autres actions pour confirmer que l'admin a tous les droits
        $this->assertTrue($this->permissionService->canPerform('create', 'project'));
        $this->assertTrue($this->permissionService->canPerform('delete', 'project'));
        $this->assertTrue($this->permissionService->canPerform('assign', 'task'));
    }

    public function testCanPerformOnUserAsResponsable(): void
    {
        // Configurer le mock Security pour simuler un responsable
        $this->security->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
            
        // Configurer isGranted pour retourner true pour ROLE_RESPONSABLE
        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($role) {
                return $role === 'ROLE_RESPONSABLE';
            });

        // Vérifier les permissions d'un responsable
        $this->assertTrue($this->permissionService->canPerform('edit', 'user'));
        $this->assertTrue($this->permissionService->canPerform('create', 'user'));
        $this->assertFalse($this->permissionService->canPerform('delete', 'user'));
        $this->assertTrue($this->permissionService->canPerform('delete', 'project'));
        $this->assertFalse($this->permissionService->canPerform('edit', 'parameter'));
    }

    public function testCanPerformOnUserAsProjectManager(): void
    {
        // Configurer le mock Security pour simuler un chef de projet
        $this->security->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
            
        // Configurer isGranted pour retourner true pour ROLE_PROJECT_MANAGER
        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($role) {
                return $role === 'ROLE_PROJECT_MANAGER';
            });

        // Vérifier les permissions d'un chef de projet
        $this->assertTrue($this->permissionService->canPerform('view', 'user'));
        $this->assertTrue($this->permissionService->canPerform('list', 'user'));
        $this->assertFalse($this->permissionService->canPerform('edit', 'user'));
        $this->assertFalse($this->permissionService->canPerform('delete', 'user'));
        
        // Permissions sur les projets
        $this->assertTrue($this->permissionService->canPerform('view', 'project'));
        $this->assertTrue($this->permissionService->canPerform('edit', 'project'));
        $this->assertTrue($this->permissionService->canPerform('create', 'project'));
        $this->assertFalse($this->permissionService->canPerform('delete', 'project'));
        
        // Permissions sur les tâches
        $this->assertTrue($this->permissionService->canPerform('delete', 'task'));
        $this->assertTrue($this->permissionService->canPerform('assign', 'task'));
    }

    public function testCanPerformOnTaskAsDeveloper(): void
    {
        // Configurer le mock Security pour simuler un développeur
        $this->security->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
            
        // Configurer isGranted pour retourner true pour ROLE_DEVELOPER
        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($role) {
                return $role === 'ROLE_DEVELOPER';
            });

        // Vérifier les permissions d'un développeur sur les tâches
        $this->assertTrue($this->permissionService->canPerform('view', 'task'));
        $this->assertTrue($this->permissionService->canPerform('edit', 'task'));
        $this->assertTrue($this->permissionService->canPerform('create', 'task'));
        $this->assertFalse($this->permissionService->canPerform('delete', 'task'));
        $this->assertFalse($this->permissionService->canPerform('assign', 'task'));
    }

    public function testCanPerformOnProjectAsAuthenticatedUser(): void
    {
        // Configurer le mock Security pour simuler un utilisateur authentifié
        $this->security->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);
            
        // Configurer isGranted pour retourner false pour tous les rôles
        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturn(false);

        // Vérifier les permissions d'un utilisateur authentifié
        $this->assertTrue($this->permissionService->canPerform('view', 'project'));
        $this->assertTrue($this->permissionService->canPerform('list', 'project'));
        $this->assertFalse($this->permissionService->canPerform('edit', 'project'));
        $this->assertFalse($this->permissionService->canPerform('create', 'project'));
        $this->assertFalse($this->permissionService->canPerform('delete', 'project'));
        
        // Permissions sur les paramètres
        $this->assertTrue($this->permissionService->canPerform('view', 'parameter'));
        $this->assertFalse($this->permissionService->canPerform('edit', 'parameter'));
    }

    public function testNoUserReturnsNoPermissions(): void
    {
        // Simuler qu'aucun utilisateur n'est connecté
        $this->security->expects($this->any())
            ->method('getUser')
            ->willReturn(null);

        // Vérifier qu'aucune permission n'est accordée
        $this->assertFalse($this->permissionService->canPerform('view', 'user'));
        $this->assertFalse($this->permissionService->canPerform('edit', 'project'));
        $this->assertFalse($this->permissionService->canPerform('create', 'customer'));
        $this->assertFalse($this->permissionService->canPerform('view', 'parameter'));
        $this->assertFalse($this->permissionService->canPerform('edit', 'task'));
    }

    public function testHasRole(): void
    {
        // Configurer le mock Security
        $this->security->expects($this->any())
            ->method('isGranted')
            ->willReturnCallback(function ($role) {
                return $role === 'ROLE_ADMIN';
            });

        // Vérifier la méthode hasRole
        $this->assertTrue($this->permissionService->hasRole('ROLE_ADMIN'));
        $this->assertFalse($this->permissionService->hasRole('ROLE_PROJECT_MANAGER'));
    }

    public function testGetUserRoles(): void
    {
        // Configurer le mock User pour retourner des rôles
        $this->user->expects($this->any())
            ->method('getRoles')
            ->willReturn(['ROLE_ADMIN', 'ROLE_USER']);
            
        // Configurer le mock Security pour retourner l'utilisateur
        $this->security->expects($this->any())
            ->method('getUser')
            ->willReturn($this->user);

        // Vérifier la méthode getUserRoles
        $roles = $this->permissionService->getUserRoles();
        $this->assertIsArray($roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    public function testGetUserRolesWithNoUser(): void
    {
        // Configurer le mock Security pour retourner null
        $this->security->expects($this->any())
            ->method('getUser')
            ->willReturn(null);

        // Vérifier que getUserRoles retourne un tableau vide
        $roles = $this->permissionService->getUserRoles();
        $this->assertIsArray($roles);
        $this->assertEmpty($roles);
    }
}
