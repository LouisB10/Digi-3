<?php
// tests/Functional/Controller/UserControllerTest.php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Enum\UserRole;

class UserControllerTest extends WebTestCase
{
    private $client;
    private $adminUser;
    private $developerUser;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $userRepository = static::getContainer()->get(UserRepository::class);
        
        // Récupérer un utilisateur admin pour les tests
        $this->adminUser = $userRepository->findOneByEmail('admin@example.com');
        
        // Récupérer un utilisateur développeur pour les tests
        $this->developerUser = $userRepository->findOneByEmail('developer@example.com');
    }

    /**
     * Test U1: Création d'un utilisateur
     */
    public function testCreateUser(): void
    {
        // Se connecter en tant qu'administrateur
        $this->client->loginUser($this->adminUser);
        
        // Accéder à la page de gestion des utilisateurs
        $this->client->request('GET', '/parameter/users');
        $this->assertResponseIsSuccessful();
        
        // Cliquer sur le bouton pour ajouter un nouvel utilisateur
        $this->client->clickLink('Ajouter un utilisateur');
        $this->assertResponseIsSuccessful();
        
        // Remplir et soumettre le formulaire
        $this->client->submitForm('Enregistrer', [
            'user[userEmail]' => 'new.user@example.com',
            'user[userFirstName]' => 'New',
            'user[userLastName]' => 'User',
            'user[plainPassword]' => 'password123',
            'user[userRole]' => UserRole::DEVELOPER->value,
        ]);
        
        // Vérifier la redirection et le message de succès
        $this->assertResponseRedirects('/parameter/users');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert.alert-success');
        
        // Vérifier que l'utilisateur a été créé en base de données
        $userRepository = static::getContainer()->get(UserRepository::class);
        $newUser = $userRepository->findOneByEmail('new.user@example.com');
        $this->assertNotNull($newUser);
        $this->assertEquals('New', $newUser->getUserFirstName());
    }

    /**
     * Test U2: Modification d'un utilisateur
     */
    public function testEditUser(): void
    {
        // Se connecter en tant qu'administrateur
        $this->client->loginUser($this->adminUser);
        
        // Créer un utilisateur temporaire pour le test
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $user = new User();
        $user->setUserEmail('edit.user@example.com');
        $user->setUserFirstName('Edit');
        $user->setUserLastName('User');
        $user->setUserRole(UserRole::DEVELOPER);
        $user->setPassword('$2y$13$hK7.rTAXSIbcrpR/KU.Rn.o5RvXx7OZbNX6gkGpA.6E0PJRQJe7Uy'); // password encodé
        $entityManager->persist($user);
        $entityManager->flush();
        
        // Accéder à la page d'édition de l'utilisateur
        $this->client->request('GET', '/parameter/users/edit/' . $user->getId());
        $this->assertResponseIsSuccessful();
        
        // Modifier et soumettre le formulaire
        $this->client->submitForm('Enregistrer', [
            'user[userEmail]' => 'edit.user@example.com',
            'user[userFirstName]' => 'Modified',
            'user[userLastName]' => 'User',
            'user[userRole]' => UserRole::PROJECT_MANAGER->value,
        ]);
        
        // Vérifier la redirection et le message de succès
        $this->assertResponseRedirects('/parameter/users');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert.alert-success');
        
        // Vérifier que les modifications ont été enregistrées
        $entityManager->refresh($user);
        $this->assertEquals('Modified', $user->getUserFirstName());
        $this->assertEquals(UserRole::PROJECT_MANAGER, $user->getUserRole());
        
        // Nettoyer la base de données
        $entityManager->remove($user);
        $entityManager->flush();
    }

    /**
     * Test U3: Suppression d'un utilisateur
     */
    public function testDeleteUser(): void
    {
        // Se connecter en tant qu'administrateur
        $this->client->loginUser($this->adminUser);
        
        // Créer un utilisateur temporaire pour le test
        $entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        $user = new User();
        $user->setUserEmail('delete.user@example.com');
        $user->setUserFirstName('Delete');
        $user->setUserLastName('User');
        $user->setUserRole(UserRole::DEVELOPER);
        $user->setPassword('$2y$13$hK7.rTAXSIbcrpR/KU.Rn.o5RvXx7OZbNX6gkGpA.6E0PJRQJe7Uy'); // password encodé
        $entityManager->persist($user);
        $entityManager->flush();
        
        $userId = $user->getId();
        
        // Accéder à la page de gestion des utilisateurs
        $this->client->request('GET', '/parameter/users');
        $this->assertResponseIsSuccessful();
        
        // Supprimer l'utilisateur (via une requête AJAX simulée)
        $this->client->request('DELETE', '/parameter/users/delete/' . $userId);
        
        // Vérifier la réponse JSON
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        // Vérifier que l'utilisateur a été supprimé
        $deletedUser = static::getContainer()->get(UserRepository::class)->find($userId);
        $this->assertNull($deletedUser);
    }

    /**
     * Test U4: Vérification des droits administrateur
     */
    public function testAdminAccess(): void
    {
        // Se connecter en tant qu'administrateur
        $this->client->loginUser($this->adminUser);
        
        // Vérifier l'accès aux différentes sections
        $this->client->request('GET', '/parameter/general');
        $this->assertResponseIsSuccessful();
        
        $this->client->request('GET', '/parameter/users');
        $this->assertResponseIsSuccessful();
        
        $this->client->request('GET', '/parameter/customers');
        $this->assertResponseIsSuccessful();
        
        $this->client->request('GET', '/project/list');
        $this->assertResponseIsSuccessful();
        
        $this->client->request('GET', '/project/management');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test U5: Vérification des droits développeur
     */
    public function testDeveloperAccess(): void
    {
        // Se connecter en tant que développeur
        $this->client->loginUser($this->developerUser);
        
        // Vérifier l'accès aux sections autorisées
        $this->client->request('GET', '/project/list');
        $this->assertResponseIsSuccessful();
        
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        
        // Vérifier que l'accès est refusé pour les sections d'administration
        $this->client->request('GET', '/parameter/users');
        $this->assertResponseStatusCodeSame(403); // Forbidden
        
        $this->client->request('GET', '/parameter/general');
        $this->assertResponseStatusCodeSame(403); // Forbidden
    }
} 