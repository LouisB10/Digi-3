<?php

namespace App\DataFixtures;

use App\Entity\Customers;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CustomerFixtures extends Fixture implements DependentFixtureInterface
{
    // Constantes pour les références
    public const CUSTOMER_ACME_REFERENCE = 'customer-acme';
    public const CUSTOMER_TECH_REFERENCE = 'customer-tech';
    public const CUSTOMER_GLOBAL_REFERENCE = 'customer-global';
    public const CUSTOMER_DIGITAL_REFERENCE = 'customer-digital';
    public const CUSTOMER_INNOV_REFERENCE = 'customer-innov';

    public function load(ObjectManager $manager): void
    {
        // Récupérer l'utilisateur admin via la référence
        $admin = $this->getReference(AppFixtures::ADMIN_USER_REFERENCE, User::class);

        $customers = [
            [
                'name' => 'ACME Corporation',
                'street' => '123 Rue de l\'Innovation',
                'zipcode' => '75001',
                'city' => 'Paris',
                'country' => 'FR',
                'vat' => 'FR12345678901',
                'siren' => '123456789',
                'reference' => self::CUSTOMER_ACME_REFERENCE
            ],
            [
                'name' => 'Tech Innovations SAS',
                'street' => '45 Avenue des Startups',
                'zipcode' => '69002',
                'city' => 'Lyon',
                'country' => 'FR',
                'vat' => 'FR98765432109',
                'siren' => '987654321',
                'reference' => self::CUSTOMER_TECH_REFERENCE
            ],
            [
                'name' => 'Global Solutions SARL',
                'street' => '78 Rue du Digital',
                'zipcode' => '31000',
                'city' => 'Toulouse',
                'country' => 'FR',
                'vat' => 'FR56789012345',
                'siren' => '567890123',
                'reference' => self::CUSTOMER_GLOBAL_REFERENCE
            ],
            [
                'name' => 'Digital Factory',
                'street' => '12 Boulevard de l\'Industrie',
                'zipcode' => '33000',
                'city' => 'Bordeaux',
                'country' => 'FR',
                'vat' => 'FR45678901234',
                'siren' => '456789012',
                'reference' => self::CUSTOMER_DIGITAL_REFERENCE
            ],
            [
                'name' => 'Innov\'Tech',
                'street' => '56 Rue de la Recherche',
                'zipcode' => '59000',
                'city' => 'Lille',
                'country' => 'FR',
                'vat' => 'FR34567890123',
                'siren' => '345678901',
                'reference' => self::CUSTOMER_INNOV_REFERENCE
            ]
        ];

        foreach ($customers as $customerData) {
            $customer = new Customers();
            $customer->setCustomerName($customerData['name'])
                     ->setCustomerAddressStreet($customerData['street'])
                     ->setCustomerAddressZipcode($customerData['zipcode'])
                     ->setCustomerAddressCity($customerData['city'])
                     ->setCustomerAddressCountry($customerData['country'])
                     ->setCustomerVAT($customerData['vat'])
                     ->setCustomerSIREN($customerData['siren'])
                     ->setCustomerUpdatedBy($admin);

            $manager->persist($customer);
            
            // Ajouter une référence pour pouvoir utiliser ce client dans d'autres fixtures
            $this->addReference($customerData['reference'], $customer);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            AppFixtures::class
        ];
    }
} 