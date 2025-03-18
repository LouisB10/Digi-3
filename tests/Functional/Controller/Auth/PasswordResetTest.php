<?php
// tests/Functional/Controller/Auth/PasswordResetTest.php

namespace App\Tests\Functional\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\UserRepository;
use App\Entity\User;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PasswordResetTest extends WebTestCase
{
    /**
     * Test A4: Mot de passe oublié
     */
    public function testForgotPassword(): void
    {
        $client = static::createClient();
        $client->request('GET', '/auth');

        $this->assertResponseIsSuccessful();
        
        // Cliquer sur le lien "Mot de passe oublié"
        $client->clickLink('Mot de passe oublié');
        
        $this->assertResponseIsSuccessful();
        
        // Récupérer un utilisateur existant
        $userRepository = static::getContainer()->get(UserRepository::class);
        $testUser = $userRepository->findOneBy(['userEmail' => 'admin@example.com']);
        
        if (!$testUser) {
            $this->markTestSkipped('Utilisateur admin@example.com non trouvé dans la base de données.');
        }
        
        // Intercepter les emails envoyés
        $emailSent = false;
        $dispatcher = static::getContainer()->get(EventDispatcherInterface::class);
        $dispatcher->addListener(MessageEvent::class, function (MessageEvent $event) use (&$emailSent, $testUser) {
            $email = $event->getMessage();
            if (!$email instanceof Email) {
                return;
            }
            
            if (in_array($testUser->getUserEmail(), $email->getTo())) {
                $emailSent = true;
            }
        });
        
        // Soumettre le formulaire de réinitialisation
        $client->submitForm('Envoyer', [
            'reset_password_request_form[email]' => $testUser->getUserEmail(),
        ]);
        
        // Vérifier la redirection vers la page de confirmation
        $this->assertResponseRedirects();
        $client->followRedirect();
        
        // Vérifier qu'un message de confirmation est affiché
        $this->assertSelectorTextContains('div', 'Un email a été envoyé');
        
        // Vérifier qu'un email a été envoyé
        $this->assertTrue($emailSent, 'Aucun email n\'a été envoyé à l\'utilisateur.');
    }
    
    /**
     * Test A4-Erreur: Tentative de réinitialisation avec un email inexistant
     */
    public function testForgotPasswordWithInvalidEmail(): void
    {
        $client = static::createClient();
        $client->request('GET', '/auth');

        $this->assertResponseIsSuccessful();
        
        // Cliquer sur le lien "Mot de passe oublié"
        $client->clickLink('Mot de passe oublié');
        
        $this->assertResponseIsSuccessful();
        
        // Soumettre le formulaire avec un email inexistant
        $client->submitForm('Envoyer', [
            'reset_password_request_form[email]' => 'nonexistent@example.com',
        ]);
        
        // Vérifier la redirection vers la page de confirmation
        // Note: Pour des raisons de sécurité, on affiche généralement le même message
        // que l'email existe ou non pour éviter les attaques par énumération
        $this->assertResponseRedirects();
        $client->followRedirect();
        
        // Vérifier qu'un message de confirmation est affiché
        $this->assertSelectorTextContains('div', 'Un email a été envoyé');
    }
} 