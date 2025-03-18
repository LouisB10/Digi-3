<?php
// tests/Performance/DashboardPerformanceTest.php

namespace App\Tests\Performance;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Entity\User;
use App\Enum\UserRole;

class DashboardPerformanceTest extends WebTestCase
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
     * Test T1: Chargement du tableau de bord
     */
    public function testDashboardLoadTime(): void
    {
        // Mesurer le temps de chargement du tableau de bord
        $startTime = microtime(true);
        
        $this->client->request('GET', '/dashboard');
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        // Vérifier que la page s'est chargée correctement
        $this->assertResponseIsSuccessful();
        
        // Vérifier que le temps de chargement est inférieur à 3 secondes
        $this->assertLessThan(
            3.0,
            $loadTime,
            sprintf('Le tableau de bord a mis %.2f secondes à charger, ce qui dépasse la limite de 3 secondes.', $loadTime)
        );
        
        // Afficher le temps de chargement pour information
        echo sprintf('Temps de chargement du tableau de bord: %.2f secondes', $loadTime);
    }

    /**
     * Test: Chargement de la liste des projets
     */
    public function testProjectsListLoadTime(): void
    {
        // Mesurer le temps de chargement de la liste des projets
        $startTime = microtime(true);
        
        $this->client->request('GET', '/projects');
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        // Vérifier que la page s'est chargée correctement
        $this->assertResponseIsSuccessful();
        
        // Vérifier que le temps de chargement est inférieur à 3 secondes
        $this->assertLessThan(
            3.0,
            $loadTime,
            sprintf('La liste des projets a mis %.2f secondes à charger, ce qui dépasse la limite de 3 secondes.', $loadTime)
        );
        
        // Afficher le temps de chargement pour information
        echo sprintf('Temps de chargement de la liste des projets: %.2f secondes', $loadTime);
    }

    /**
     * Test: Chargement de la liste des utilisateurs
     */
    public function testUsersListLoadTime(): void
    {
        // Mesurer le temps de chargement de la liste des utilisateurs
        $startTime = microtime(true);
        
        $this->client->request('GET', '/parameter/users');
        
        $endTime = microtime(true);
        $loadTime = $endTime - $startTime;
        
        // Vérifier que la page s'est chargée correctement
        $this->assertResponseIsSuccessful();
        
        // Vérifier que le temps de chargement est inférieur à 3 secondes
        $this->assertLessThan(
            3.0,
            $loadTime,
            sprintf('La liste des utilisateurs a mis %.2f secondes à charger, ce qui dépasse la limite de 3 secondes.', $loadTime)
        );
        
        // Afficher le temps de chargement pour information
        echo sprintf('Temps de chargement de la liste des utilisateurs: %.2f secondes', $loadTime);
    }
} 