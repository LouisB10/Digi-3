<?php
// tests/Functional/Controller/User/UserPermissionsTest.php

namespace App\Tests\Functional\Controller\User;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Enum\UserRole;

class UserPermissionsTest extends WebTestCase
{
    private $userRepository;
    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $this->passwordHasher = static::getContainer()->get('security.user_password_hasher');
    }

    /**
     * Test U4: Vérification des droits administrateur
     */
    public function testAdminAccess(): void
    {
        $client = static::createClient();
        
        // Récupérer un utilisateur administrateur
        $adminUser = $this->userRepository->findOneBy(['userEmail' => 'admin@example.com']);
        
        if (!$adminUser) {
            $adminUser = $this->createUser('Admin', 'User', 'admin@example.com', UserRole::ADMIN);
        }
        
        // Connecter l'administrateur
        $client->loginUser($adminUser);
        
        // Vérifier l'accès à la page d'administration des utilisateurs
        $client->request('GET', '/parameter/users');
        $this->assertResponseIsSuccessful();
        
        // Vérifier l'accès à la page d'administration des projets
        $client->request('GET', '/projects');
        $this->assertResponseIsSuccessful();
        
        // Vérifier l'accès à la page d'administration des paramètres
        $client->request('GET', '/parameter/config');
        $this->assertResponseIsSuccessful();
        
        // Vérifier l'accès à la page d'administration des clients
        $client->request('GET', '/parameter/customers');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test U5: Vérification des droits développeur full-web
     */
    public function testDeveloperAccess(): void
    {
        $client = static::createClient();
        
        // Créer un utilisateur développeur
        $developerUser = $this->createUser('Developer', 'User', 'developer_' . uniqid() . '@example.com', UserRole::DEVELOPER);
        
        // Connecter le développeur
        $client->loginUser($developerUser);
        
        // Vérifier l'accès aux projets (autorisé)
        $client->request('GET', '/projects');
        $this->assertResponseIsSuccessful();
        
        // Vérifier l'accès à la page d'administration des utilisateurs (refusé)
        $client->request('GET', '/parameter/users');
        $this->assertResponseStatusCodeSame(403); // Forbidden
        
        // Vérifier l'accès à la page d'administration des paramètres (refusé)
        $client->request('GET', '/parameter/config');
        $this->assertResponseStatusCodeSame(403); // Forbidden
        
        // Vérifier l'accès à la page d'administration des clients (refusé)
        $client->request('GET', '/parameter/customers');
        $this->assertResponseStatusCodeSame(403); // Forbidden
    }

    /**
     * Test U5-bis: Vérification des droits chef de projet
     */
    public function testProjectManagerAccess(): void
    {
        $client = static::createClient();
        
        // Créer un utilisateur chef de projet
        $pmUser = $this->createUser('Project', 'Manager', 'pm_' . uniqid() . '@example.com', UserRole::PROJECT_MANAGER);
        
        // Connecter le chef de projet
        $client->loginUser($pmUser);
        
        // Vérifier l'accès aux projets (autorisé)
        $client->request('GET', '/projects');
        $this->assertResponseIsSuccessful();
        
        // Vérifier l'accès à la création de projets (autorisé)
        $client->request('GET', '/projects/create');
        $this->assertResponseIsSuccessful();
        
        // Vérifier l'accès à la page d'administration des utilisateurs (refusé)
        $client->request('GET', '/parameter/users');
        $this->assertResponseStatusCodeSame(403); // Forbidden
        
        // Vérifier l'accès à la page d'administration des paramètres (refusé)
        $client->request('GET', '/parameter/config');
        $this->assertResponseStatusCodeSame(403); // Forbidden
    }

    /**
     * Méthode utilitaire pour créer un utilisateur de test avec un rôle spécifique
     */
    private function createUser(string $firstName, string $lastName, string $email, UserRole $role): User
    {
        $user = User::create(
            $firstName,
            $lastName,
            $email,
            $this->passwordHasher->hashPassword(new User(), 'Test@123456')
        );
        
        $user->setUserRole($role);
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }
} 