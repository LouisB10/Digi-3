<?php
// tests/Functional/Controller/SecurityControllerTest.php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;

class SecurityControllerTest extends WebTestCase
{
    /**
     * Test A1: Connexion avec identifiants valides
     */
    public function testSuccessfulLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Connexion');

        // Soumettre le formulaire de connexion
        $client->submitForm('Se connecter', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        // Vérifier la redirection vers le tableau de bord
        $this->assertResponseRedirects('/dashboard');
        $client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Tableau de bord');
    }

    /**
     * Test A2: Connexion avec identifiants invalides
     */
    public function testFailedLogin(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();

        // Soumettre le formulaire avec des identifiants incorrects
        $client->submitForm('Se connecter', [
            'email' => 'admin@example.com',
            'password' => 'wrong_password',
        ]);

        // Vérifier que nous restons sur la page de connexion avec un message d'erreur
        $this->assertResponseRedirects('/login');
        $client->followRedirect();
        $this->assertSelectorExists('.alert.alert-danger');
    }

    /**
     * Test A3: Déconnexion
     */
    public function testLogout(): void
    {
        $client = static::createClient();
        
        // Se connecter d'abord
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneByEmail('admin@example.com');
        $client->loginUser($testUser);

        // Vérifier que l'utilisateur est connecté
        $client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();

        // Se déconnecter
        $client->request('GET', '/logout');
        
        // Vérifier la redirection vers la page de connexion
        $client->request('GET', '/dashboard');
        $this->assertResponseRedirects('/login');
    }

    /**
     * Test A4: Mot de passe oublié
     */
    public function testForgotPassword(): void
    {
        $client = static::createClient();
        $client->request('GET', '/reset-password');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('form[name="reset_password_request_form"]');

        // Soumettre le formulaire de réinitialisation
        $client->submitForm('Envoyer', [
            'reset_password_request_form[email]' => 'admin@example.com',
        ]);

        // Vérifier la redirection vers la page de confirmation
        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertSelectorTextContains('div', 'Un email a été envoyé');
    }
} 