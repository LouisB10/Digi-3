<?php
// tests/Security/SecurityTest.php

namespace App\Tests\Security;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Enum\UserRole;

class SecurityTest extends WebTestCase
{
    private $client;
    private $userRepository;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    /**
     * Test: Protection des routes contre les accès non authentifiés
     */
    public function testProtectedRoutesRequireAuthentication(): void
    {
        // Liste des routes protégées à tester
        $protectedRoutes = [
            '/dashboard',
            '/projects',
            '/parameter/users',
            '/parameter/general',
            '/parameter/config',
            '/parameter/customers',
        ];
        
        foreach ($protectedRoutes as $route) {
            $this->client->request('GET', $route);
            
            // Vérifier que l'accès est refusé et redirigé vers la page d'authentification
            $this->assertResponseRedirects('/auth');
        }
    }

    /**
     * Test: Protection contre les injections SQL
     */
    public function testSqlInjectionProtection(): void
    {
        // Se connecter en tant qu'utilisateur
        $user = $this->userRepository->findOneBy(['userEmail' => 'admin@example.com']);
        if (!$user) {
            $this->markTestSkipped('Utilisateur admin@example.com non trouvé dans la base de données.');
        }
        $this->client->loginUser($user);
        
        // Tenter une injection SQL dans un paramètre de requête
        $this->client->request('GET', '/projects/search?query=test\' OR \'1\'=\'1');
        
        // Vérifier que la page s'est chargée correctement (pas d'erreur 500)
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test: Protection contre les attaques XSS
     */
    public function testXssProtection(): void
    {
        // Se connecter en tant qu'utilisateur
        $user = $this->userRepository->findOneBy(['userEmail' => 'admin@example.com']);
        if (!$user) {
            $this->markTestSkipped('Utilisateur admin@example.com non trouvé dans la base de données.');
        }
        $this->client->loginUser($user);
        
        // Tenter une attaque XSS dans un formulaire
        $this->client->request('POST', '/projects/create', [
            'project_form' => [
                'projectName' => '<script>alert("XSS")</script>',
                'projectDescription' => 'Description normale',
                'projectDateStart' => (new \DateTime())->format('Y-m-d'),
                'projectDateEnd' => (new \DateTime('+1 month'))->format('Y-m-d'),
            ]
        ]);
        
        // Suivre la redirection
        $this->client->followRedirect();
        
        // Vérifier que le script n'est pas exécuté (échappé)
        $this->assertStringNotContainsString(
            '<script>alert("XSS")</script>',
            $this->client->getResponse()->getContent()
        );
        
        // Vérifier que le contenu est échappé
        $this->assertStringContainsString(
            '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
            $this->client->getResponse()->getContent()
        );
    }

    /**
     * Test: Protection contre les attaques CSRF
     */
    public function testCsrfProtection(): void
    {
        // Se connecter en tant qu'utilisateur
        $user = $this->userRepository->findOneBy(['userEmail' => 'admin@example.com']);
        if (!$user) {
            $this->markTestSkipped('Utilisateur admin@example.com non trouvé dans la base de données.');
        }
        $this->client->loginUser($user);
        
        // Tenter de soumettre un formulaire sans token CSRF
        $this->client->request('POST', '/parameter/general/update-email', [
            'email_form' => [
                'email' => 'new_email@example.com',
                'password' => 'password',
            ]
        ]);
        
        // Vérifier que la requête est rejetée (400 Bad Request ou redirection avec erreur)
        $this->assertResponseStatusCodeSame(400);
    }

    /**
     * Test: Validation des entrées utilisateur
     */
    public function testInputValidation(): void
    {
        // Se connecter en tant qu'utilisateur
        $user = $this->userRepository->findOneBy(['userEmail' => 'admin@example.com']);
        if (!$user) {
            $this->markTestSkipped('Utilisateur admin@example.com non trouvé dans la base de données.');
        }
        $this->client->loginUser($user);
        
        // Soumettre un formulaire avec des données invalides
        $this->client->request('POST', '/parameter/general/update-password', [
            'password_form' => [
                'actual_password' => 'password',
                'password' => 'short', // Mot de passe trop court
                'confirm_password' => 'short',
            ]
        ]);
        
        // Vérifier que le formulaire est rejeté avec des erreurs de validation
        $this->assertResponseRedirects('/parameter/general');
        $this->client->followRedirect();
        $this->assertSelectorExists('.alert-danger');
    }

    /**
     * Test: Limitation du nombre de tentatives de connexion
     */
    public function testLoginRateLimiting(): void
    {
        // Tenter de se connecter plusieurs fois avec des identifiants incorrects
        for ($i = 0; $i < 5; $i++) {
            $this->client->request('POST', '/auth', [
                'login_form' => [
                    'email' => 'admin@example.com',
                    'password' => 'wrong_password_' . $i,
                ]
            ]);
            $this->client->followRedirect();
        }
        
        // Tenter une connexion supplémentaire
        $this->client->request('POST', '/auth', [
            'login_form' => [
                'email' => 'admin@example.com',
                'password' => 'wrong_password_again',
            ]
        ]);
        $this->client->followRedirect();
        
        // Vérifier qu'un message d'erreur de limitation est affiché
        $this->assertSelectorTextContains('.error', 'tentatives');
    }
} 