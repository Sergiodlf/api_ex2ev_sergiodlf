<?php

namespace App\Controller;

use App\Dto\BookingNewDto;
use App\Entity\Booking;
use App\Enum\ClientType;
use App\Repository\ActivityRepository;
use App\Repository\BookingRepository;
use App\Repository\ClientRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

class BookingController extends AbstractController
{
    #[Route('/bookings', methods: ['POST'])]
    public function create(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $em,
        ClientRepository $clientRepository,
        ActivityRepository $activityRepository,
        BookingRepository $bookingRepository
    ): JsonResponse {
        // 1 Deserializar JSON
        $data = json_decode($request->getContent(), true);

        $dto = new BookingNewDto();
        $dto->activity_id = $data['activity_id'] ?? null;
        $dto->client_id = $data['client_id'] ?? null;

        // 2 Validar DTO
        $errors = $validator->validate($dto);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        // 3️ Comprobar existencia
        $client = $clientRepository->find($dto->client_id);
        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $activity = $activityRepository->find($dto->activity_id);
        if (!$activity) {
            return $this->json(['error' => 'Activity not found'], 404);
        }

        // 5️ Regla STANDARD (máx 2 por semana)
        if ($client->getType() === ClientType::STANDARD) {
            $date = $activity->getDateStart();

            $weekStart = (clone $date)->modify('monday this week')->setTime(0, 0);
            $weekEnd = (clone $date)->modify('sunday this week')->setTime(23, 59, 59);

            $weeklyCount = $bookingRepository->countClientBookingsInWeek(
                $client->getId(),
                $weekStart,
                $weekEnd
            );

            if ($weeklyCount >= 2) {
                return $this->json(
                    ['error' => 'Weekly limit exceeded'],
                    Response::HTTP_FORBIDDEN
                );
            }
        }

        // 3.5 Evitar duplicados: mismo cliente, misma actividad
        $existing = $bookingRepository->findOneBy([
            'client' => $client,
            'activity' => $activity,
        ]);

        if ($existing) {
            return $this->json(
                ['error' => 'Client already booked this activity'],
                Response::HTTP_CONFLICT
            );
        }

        // 4️ Comprobar plazas
        $currentBookings = $bookingRepository->count([
            'activity' => $activity
        ]);

        if ($currentBookings >= $activity->getMaxParticipants()) {
            return $this->json(
                ['error' => 'No free slots'],
                Response::HTTP_CONFLICT
            );
        }

        // 6️ Crear booking
        $booking = new Booking();
        $booking->setClient($client);
        $booking->setActivity($activity);

        $em->persist($booking);
        $em->flush();

        // 7️ Respuesta (OpenAPI) - activity completa
        // clients_signed debe ser el número actual de reservas de ESA actividad
        $clientsSigned = $bookingRepository->count([
            'activity' => $activity
        ]);

        $playList = [];
        foreach ($activity->getSongs() as $song) {
            $playList[] = [
                'id' => $song->getId(),
                'name' => $song->getName(),
                'duration_seconds' => $song->getDurationSeconds(),
            ];
        }

        // Construimos el array a mano para controlar el ORDEN de claves
        $response = [
            'id' => $booking->getId(),
            'activity' => [
                'id' => $activity->getId(),
                'max_participants' => $activity->getMaxParticipants(),
                'clients_signed' => $clientsSigned,
                'type' => $activity->getType()->value,
                'play_list' => $playList,
                'date_start' => $activity->getDateStart()->format(DATE_ATOM),
                'date_end' => $activity->getDateEnd()->format(DATE_ATOM),
            ],
            'client_id' => $client->getId(),
        ];

        // OpenAPI/YAML de tu proyecto define 200 para OK (si quieres ser ultra estricto con el YAML)
        return $this->json($response, Response::HTTP_OK);
    }
}
