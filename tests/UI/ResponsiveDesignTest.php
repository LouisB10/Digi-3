<?php
// tests/UI/ResponsiveDesignTest.php

namespace App\Tests\UI;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Enum\UserRole;

class ResponsiveDesignTest extends WebTestCase
{
    private $client;
    private $testUser;
    private $userRepository;
    private $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine.orm.entity_manager');
        
        // Récupérer ou créer un utilisateur de test
        $this->testUser = $this->userRepository->findOneBy(['userEmail' => 'admin@example.com']);
        
        if (!$this->testUser) {
            $passwordHasher = static::getContainer()->get('security.user_password_hasher');
            $this->testUser = User::create(
                'Admin',
                'Test',
                'admin@example.com',
                $passwordHasher->hashPassword(new User(), 'Test@123456')
            );
            $this->testUser->setUserRole(UserRole::ADMIN);
            $this->entityManager->persist($this->testUser);
            $this->entityManager->flush();
        }
        
        // Connecter l'utilisateur
        $this->client->loginUser($this->testUser);
    }

    /**
     * Test T2: Compatibilité mobile - Smartphone
     * 
     * Note: Ce test vérifie seulement la présence de la méta viewport et des classes CSS
     * responsives. Pour un test complet, il faudrait utiliser Selenium ou un autre outil
     * de test d'interface utilisateur.
     */
    public function testMobileCompatibilitySmartphone(): void
    {
        // Simuler un smartphone
        $this->client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1');
        
        // Accéder au tableau de bord
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        
        // Vérifier la présence de la méta viewport
        $this->assertSelectorExists('meta[name="viewport"]');
        
        // Vérifier la présence d'éléments responsifs
        $this->assertSelectorExists('.container');
        $this->assertSelectorExists('.row');
        
        // Vérifier que le menu mobile est présent
        $this->assertSelectorExists('.mobile-menu, .navbar-toggler');
    }

    /**
     * Test T2: Compatibilité mobile - Tablette
     */
    public function testMobileCompatibilityTablet(): void
    {
        // Simuler une tablette
        $this->client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1');
        
        // Accéder au tableau de bord
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
        
        // Vérifier la présence de la méta viewport
        $this->assertSelectorExists('meta[name="viewport"]');
        
        // Vérifier la présence d'éléments responsifs
        $this->assertSelectorExists('.container');
        $this->assertSelectorExists('.row');
    }

    /**
     * Test T3: Test multi-navigateurs - Chrome
     * 
     * Note: Ce test simule seulement l'user-agent de Chrome. Pour un test complet,
     * il faudrait utiliser Selenium avec différents navigateurs.
     */
    public function testChromeCompatibility(): void
    {
        // Simuler Chrome
        $this->client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        
        // Accéder au tableau de bord
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test T3: Test multi-navigateurs - Firefox
     */
    public function testFirefoxCompatibility(): void
    {
        // Simuler Firefox
        $this->client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0');
        
        // Accéder au tableau de bord
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test T3: Test multi-navigateurs - Safari
     */
    public function testSafariCompatibility(): void
    {
        // Simuler Safari
        $this->client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15');
        
        // Accéder au tableau de bord
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
    }

    /**
     * Test T3: Test multi-navigateurs - Edge
     */
    public function testEdgeCompatibility(): void
    {
        // Simuler Edge
        $this->client->setServerParameter('HTTP_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36 Edg/91.0.864.59');
        
        // Accéder au tableau de bord
        $this->client->request('GET', '/dashboard');
        $this->assertResponseIsSuccessful();
    }
} 