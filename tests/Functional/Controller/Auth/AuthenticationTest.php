<?php
// tests/Functional/Controller/Auth/AuthenticationTest.php

namespace App\Tests\Functional\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Entity\User;

class AuthenticationTest extends WebTestCase
{
    /**
     * Test A1: Connexion avec identifiants valides
     */
    public function testSuccessfulLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/auth');

        $this->assertResponseIsSuccessful();
        
        // Soumettre le formulaire de connexion
        $client->submitForm('Se connecter', [
            'login_form[email]' => 'admin@example.com',
            'login_form[password]' => 'password',
        ]);

        // Vérifier la redirection vers le tableau de bord
        $this->assertResponseRedirects('/dashboard');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.dashboard-container');
    }

    /**
     * Test A2: Connexion avec identifiants invalides
     */
    public function testFailedLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/auth');

        $this->assertResponseIsSuccessful();

        // Soumettre le formulaire avec des identifiants incorrects
        $client->submitForm('Se connecter', [
            'login_form[email]' => 'admin@example.com',
            'login_form[password]' => 'wrong_password',
        ]);

        // Vérifier que nous restons sur la page de connexion avec un message d'erreur
        $this->assertResponseRedirects('/auth');
        $client->followRedirect();
        $this->assertSelectorExists('.error');
    }

    /**
     * Test A3: Déconnexion
     */
    public function testLogout(): void
    {
        $client = static::createClient();
        
        // Se connecter d'abord
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['userEmail' => 'admin@example.com']);
        
        if (!$testUser) {
            $this->markTestSkipped('Utilisateur admin@example.com non trouvé dans la base de données.');
        }
        
        $client->loginUser($testUser);

        // Vérifier que l'utilisateur est connecté en accédant à une page protégée
        $client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        // Se déconnecter
        $client->request('GET', '/logout');
        
        // Vérifier la redirection vers la page de connexion
        $client->request('GET', '/dashboard');
        $this->assertResponseRedirects('/auth');
        
        // Suivre la redirection pour vérifier qu'on arrive bien sur la page d'authentification
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#loginSection');
    }
} 