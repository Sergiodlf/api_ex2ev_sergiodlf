<?php

namespace App\DataFixtures;

use App\Entity\Activity;
use App\Entity\Booking;
use App\Entity\Client;
use App\Entity\Song;
use App\Enum\ActivityType;
use App\Enum\ClientType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        /*
         * CLIENTES
         */
        $client1 = new Client();
        $client1->setName('Ana Standard');
        $client1->setEmail('ana.standard@example.com');
        $client1->setType(ClientType::STANDARD);

        $client2 = new Client();
        $client2->setName('Luis Premium');
        $client2->setEmail('luis.premium@example.com');
        $client2->setType(ClientType::PREMIUM);

        $manager->persist($client1);
        $manager->persist($client2);

        /*
         * ACTIVIDADES
         */
        $activity1 = new Activity();
        $activity1->setType(ActivityType::BODY_PUMP);
        $activity1->setMaxParticipants(2);
        $activity1->setDateStart(new \DateTimeImmutable('+1 day 10:00'));
        $activity1->setDateEnd(new \DateTimeImmutable('+1 day 11:00'));

        $activity2 = new Activity();
        $activity2->setType(ActivityType::SPINNING);
        $activity2->setMaxParticipants(3);
        $activity2->setDateStart(new \DateTimeImmutable('+2 days 18:00'));
        $activity2->setDateEnd(new \DateTimeImmutable('+2 days 19:00'));

        $manager->persist($activity1);
        $manager->persist($activity2);

        /*
         * CANCIONES (playlist 1–M)
         */
        $song1 = new Song();
        $song1->setName('Eye of the Tiger');
        $song1->setDurationSeconds(245);
        $song1->setActivity($activity1);

        $song2 = new Song();
        $song2->setName('Lose Yourself');
        $song2->setDurationSeconds(326);
        $song2->setActivity($activity1);

        $song3 = new Song();
        $song3->setName('Titanium');
        $song3->setDurationSeconds(245);
        $song3->setActivity($activity2);

        $manager->persist($song1);
        $manager->persist($song2);
        $manager->persist($song3);

        /*
         * RESERVA INICIAL (para probar onlyfree y clients_signed)
         */
        $booking = new Booking();
        $booking->setClient($client1);
        $booking->setActivity($activity1);

        $manager->persist($booking);

        $manager->flush();
    }
}
