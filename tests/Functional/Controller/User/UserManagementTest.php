<?php
// tests/Functional/Controller/User/UserManagementTest.php

namespace App\Tests\Functional\Controller\User;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Enum\UserRole;

class UserManagementTest extends WebTestCase
{
    private $client;
    private $adminUser;
    private $userRepository;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        
        // Récupérer un utilisateur administrateur
        $this->adminUser = $this->userRepository->findOneBy(['userEmail' => 'admin@example.com']);
        
        if (!$this->adminUser) {
            $this->markTestSkipped('Utilisateur admin@example.com non trouvé dans la base de données.');
        }
        
        // Connecter l'administrateur
        $this->client->loginUser($this->adminUser);
    }

    /**
     * Test U1: Création d'un utilisateur
     */
    public function testCreateUser(): void
    {
        // Accéder à la page de gestion des utilisateurs
        $this->client->request('GET', '/parameter/users');
        $this->assertResponseIsSuccessful();
        
        // Cliquer sur le bouton pour ajouter un nouvel utilisateur
        $this->client->clickLink('Ajouter un utilisateur');
        $this->assertResponseIsSuccessful();
        
        // Générer un email unique pour éviter les conflits
        $uniqueEmail = 'test_user_' . uniqid() . '@example.com';
        
        // Soumettre le formulaire de création d'utilisateur
        $this->client->submitForm('Créer', [
            'user_form[userFirstName]' => 'Test',
            'user_form[userLastName]' => 'Utilisateur',
            'user_form[userEmail]' => $uniqueEmail,
            'user_form[password]' => 'Test@123456',
            'user_form[userRole]' => UserRole::DEVELOPER->value,
        ]);
        
        // Vérifier la redirection vers la liste des utilisateurs
        $this->assertResponseRedirects('/parameter/users');
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que l'utilisateur a été créé en base de données
        $newUser = $this->userRepository->findOneBy(['userEmail' => $uniqueEmail]);
        $this->assertNotNull($newUser);
        $this->assertEquals('Test', $newUser->getUserFirstName());
        $this->assertEquals('Utilisateur', $newUser->getUserLastName());
        $this->assertEquals(UserRole::DEVELOPER, $newUser->getUserRole());
    }

    /**
     * Test U2: Modification d'un utilisateur
     */
    public function testUpdateUser(): void
    {
        // Créer un utilisateur de test
        $testUser = $this->createTestUser();
        
        // Accéder à la page de gestion des utilisateurs
        $this->client->request('GET', '/parameter/users');
        $this->assertResponseIsSuccessful();
        
        // Cliquer sur le bouton pour modifier l'utilisateur
        $this->client->request('GET', '/parameter/users/edit/' . $testUser->getId());
        $this->assertResponseIsSuccessful();
        
        // Soumettre le formulaire de modification
        $this->client->submitForm('Modifier', [
            'user_form[userFirstName]' => 'Test Modifié',
            'user_form[userLastName]' => 'Utilisateur Modifié',
            'user_form[userRole]' => UserRole::PROJECT_MANAGER->value,
        ]);
        
        // Vérifier la redirection vers la liste des utilisateurs
        $this->assertResponseRedirects('/parameter/users');
        $this->client->followRedirect();
        
        // Vérifier la présence d'un message de succès
        $this->assertSelectorExists('.alert-success');
        
        // Vérifier que l'utilisateur a été modifié en base de données
        $this->entityManager->refresh($testUser);
        $this->assertEquals('Test Modifié', $testUser->getUserFirstName());
        $this->assertEquals('Utilisateur Modifié', $testUser->getUserLastName());
        $this->assertEquals(UserRole::PROJECT_MANAGER, $testUser->getUserRole());
    }

    /**
     * Test U3: Suppression d'un utilisateur
     */
    public function testDeleteUser(): void
    {
        // Créer un utilisateur de test
        $testUser = $this->createTestUser();
        $userId = $testUser->getId();
        
        // Accéder à la page de gestion des utilisateurs
        $this->client->request('GET', '/parameter/users');
        $this->assertResponseIsSuccessful();
        
        // Soumettre la requête de suppression
        $this->client->request('DELETE', '/parameter/users/delete/' . $userId);
        
        // Vérifier la réponse JSON
        $this->assertResponseIsSuccessful();
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertTrue($responseData['success']);
        
        // Vérifier que l'utilisateur a été supprimé de la base de données
        $deletedUser = $this->userRepository->find($userId);
        $this->assertNull($deletedUser);
    }

    /**
     * Méthode utilitaire pour créer un utilisateur de test
     */
    private function createTestUser(): User
    {
        $uniqueEmail = 'test_user_' . uniqid() . '@example.com';
        $passwordHasher = static::getContainer()->get('security.user_password_hasher');
        
        $user = User::create(
            'Test',
            'Utilisateur',
            $uniqueEmail,
            $passwordHasher->hashPassword(new User(), 'Test@123456')
        );
        
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }
} 