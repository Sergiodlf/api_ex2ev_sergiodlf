<?php

namespace App\DataFixtures;

use App\Entity\Activity;
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
         * =========================
         * CLIENTES (10)
         * =========================
         */
        $clients = [];

        for ($i = 1; $i <= 10; $i++) {
            $client = new Client();
            $client->setName("Cliente $i");
            $client->setEmail("cliente$i@example.com");

            // Alternamos STANDARD / PREMIUM
            $client->setType(
                $i % 2 === 0 ? ClientType::PREMIUM : ClientType::STANDARD
            );

            $manager->persist($client);
            $clients[] = $client;
        }

        /*
         * =========================
         * ACTIVIDADES (10)
         * max_participants = 5
         * =========================
         */
        $activities = [];
        $types = [
            ActivityType::BODY_PUMP,
            ActivityType::SPINNING,
            ActivityType::CORE,
        ];

        for ($i = 1; $i <= 10; $i++) {
            $activity = new Activity();

            $activity->setType($types[$i % 3]);
            $activity->setMaxParticipants(5);

            // Fechas escalonadas
            $start = new \DateTimeImmutable("+$i days 10:00");
            $end = $start->modify('+1 hour');

            $activity->setDateStart($start);
            $activity->setDateEnd($end);

            $manager->persist($activity);
            $activities[] = $activity;
        }

        /*
         * =========================
         * SONGS (playlist 1–M)
         * =========================
         * Dejamos una playlist simple:
         * - 2 canciones para BodyPump
         * - 1 canción para Spinning
         * - 1 canción para Core
         */
        foreach ($activities as $activity) {
            if ($activity->getType() === ActivityType::BODY_PUMP) {
                $song1 = new Song();
                $song1->setName('Eye of the Tiger');
                $song1->setDurationSeconds(245);
                $song1->setActivity($activity);

                $song2 = new Song();
                $song2->setName('Lose Yourself');
                $song2->setDurationSeconds(326);
                $song2->setActivity($activity);

                $manager->persist($song1);
                $manager->persist($song2);
            }

            if ($activity->getType() === ActivityType::SPINNING) {
                $song = new Song();
                $song->setName('Titanium');
                $song->setDurationSeconds(245);
                $song->setActivity($activity);

                $manager->persist($song);
            }

            if ($activity->getType() === ActivityType::CORE) {
                $song = new Song();
                $song->setName('Stronger');
                $song->setDurationSeconds(210);
                $song->setActivity($activity);

                $manager->persist($song);
            }
        }

        $manager->flush();
    }
}
