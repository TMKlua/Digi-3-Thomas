<?php

namespace App\DataFixtures;

use App\Entity\Customers;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class CustomerFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer l'utilisateur admin pour param_user_maj
        $admin = $manager->getRepository(User::class)->findOneBy(['userEmail' => 'admin@digiworks.fr']);

        $customers = [
            [
                'name' => 'ACME Corporation',
                'street' => '123 Rue de l\'Innovation',
                'zipcode' => '75001',
                'city' => 'Paris',
                'country' => 'FR',
                'vat' => 'FR12345678901',
                'siren' => '123456789'
            ],
            [
                'name' => 'Tech Innovations SAS',
                'street' => '45 Avenue des Startups',
                'zipcode' => '69002',
                'city' => 'Lyon',
                'country' => 'FR',
                'vat' => 'FR98765432109',
                'siren' => '987654321'
            ],
            [
                'name' => 'Global Solutions SARL',
                'street' => '78 Rue du Digital',
                'zipcode' => '31000',
                'city' => 'Toulouse',
                'country' => 'FR',
                'vat' => 'FR56789012345',
                'siren' => '567890123'
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
                     ->setCustomerUserMaj($admin ? $admin->getId() : null)
                     ->setCustomerDateFrom(new \DateTime());

            $manager->persist($customer);
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
