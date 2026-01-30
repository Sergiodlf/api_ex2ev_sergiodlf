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

        // 6️ Crear booking
        $booking = new Booking();
        $booking->setClient($client);
        $booking->setActivity($activity);

        $em->persist($booking);
        $em->flush();

        // 7️ Respuesta (OpenAPI)
        return $this->json([
            'id' => $booking->getId(),
            'client_id' => $client->getId(),
            'activity' => [
                'id' => $activity->getId(),
                'type' => $activity->getType()->value,
                'max_participants' => $activity->getMaxParticipants(),
                'date_start' => $activity->getDateStart()->format(DATE_ATOM),
                'date_end' => $activity->getDateEnd()->format(DATE_ATOM),
            ],
        ], Response::HTTP_CREATED);
    }
}
