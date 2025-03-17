<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    // Constantes pour les références
    public const ADMIN_USER_REFERENCE = 'admin-user';
    public const RESPONSABLE_USER_REFERENCE = 'responsable-user';
    public const PROJECT_MANAGER_USER_REFERENCE = 'project-manager-user';
    public const LEAD_DEV_USER_REFERENCE = 'lead-dev-user';
    public const DEV_USER_REFERENCE = 'dev-user';
    public const STANDARD_USER_REFERENCE = 'standard-user';
    
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Administrateur principal
        $admin = $this->createUser(
            'John', 
            'Doe', 
            'admin@digiworks.fr', 
            'Admin123!',
            UserRole::ADMIN
        );
        $manager->persist($admin);
        $this->addReference(self::ADMIN_USER_REFERENCE, $admin);

        // Responsable
        $responsable = $this->createUser(
            'Marie', 
            'Dupont', 
            'responsable@digiworks.fr', 
            'Responsable123!',
            UserRole::RESPONSABLE
        );
        $manager->persist($responsable);
        $this->addReference(self::RESPONSABLE_USER_REFERENCE, $responsable);

        // Chef de Projet
        $projectManager = $this->createUser(
            'Pierre', 
            'Martin', 
            'pm@digiworks.fr', 
            'ProjectManager123!',
            UserRole::PROJECT_MANAGER
        );
        $manager->persist($projectManager);
        $this->addReference(self::PROJECT_MANAGER_USER_REFERENCE, $projectManager);

        // Lead Développeur
        $leadDeveloper = $this->createUser(
            'Sophie', 
            'Leroy', 
            'lead@digiworks.fr', 
            'LeadDev123!',
            UserRole::LEAD_DEVELOPER
        );
        $manager->persist($leadDeveloper);
        $this->addReference(self::LEAD_DEV_USER_REFERENCE, $leadDeveloper);

        // Développeur
        $developer = $this->createUser(
            'Lucas', 
            'Blanc', 
            'dev@digiworks.fr', 
            'Dev123!',
            UserRole::DEVELOPER
        );
        $manager->persist($developer);
        $this->addReference(self::DEV_USER_REFERENCE, $developer);

        // Développeur 2
        $developer2 = $this->createUser(
            'Julie', 
            'Moreau', 
            'julie@digiworks.fr', 
            'Dev123!',
            UserRole::DEVELOPER
        );
        $manager->persist($developer2);
        $this->addReference('dev-user-2', $developer2);

        // Développeur 3
        $developer3 = $this->createUser(
            'Thomas', 
            'Dubois', 
            'thomas@digiworks.fr', 
            'Dev123!',
            UserRole::DEVELOPER
        );
        $manager->persist($developer3);
        $this->addReference('dev-user-3', $developer3);

        // Utilisateur standard
        $user = $this->createUser(
            'Emma', 
            'Durand', 
            'user@digiworks.fr', 
            'User123!',
            UserRole::USER
        );
        $manager->persist($user);
        $this->addReference(self::STANDARD_USER_REFERENCE, $user);

        $manager->flush();
    }

    private function createUser(
        string $firstName, 
        string $lastName, 
        string $email, 
        string $plainPassword,
        UserRole $role
    ): User {
        $user = new User();
        $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);

        $user->setUserFirstName($firstName)
             ->setUserLastName($lastName)
             ->setUserEmail($email)
             ->setPassword($hashedPassword)
             ->setUserDateFrom(new \DateTime())
             ->setUserAvatar('build/images/account/default-avatar.jpg')
             ->setUserRole($role);

        return $user;
    }
}
