<?php

namespace App\Controller;

use App\Dto\ClientDto;
use App\Dto\StatisticsByTypeDto;
use App\Dto\StatisticsByYearDto;
use App\Repository\BookingRepository;
use App\Repository\ClientRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class ClientController extends AbstractController
{
    #[Route('/clients/{id}', methods: ['GET'])]
    public function getClient(
        int $id,
        Request $request,
        ClientRepository $clientRepository,
        BookingRepository $bookingRepository
    ): JsonResponse {
        $client = $clientRepository->find($id);

        if (!$client) {
            return $this->json(['error' => 'Client not found'], 404);
        }

        $withBookings = filter_var(
            $request->query->get('with_bookings'),
            FILTER_VALIDATE_BOOLEAN
        );

        $withStatistics = filter_var(
            $request->query->get('with_statistics'),
            FILTER_VALIDATE_BOOLEAN
        );

        $dto = new ClientDto();
        $dto->id = $client->getId();
        $dto->name = $client->getName();
        $dto->email = $client->getEmail();
        $dto->type = $client->getType()->value;

        /*
         * BOOKINGS
         */
        if ($withBookings) {
            foreach ($client->getBookings() as $booking) {
                $activity = $booking->getActivity();

                $dto->activities_booked[] = [
                    'id' => $booking->getId(),
                    'activity' => [
                        'id' => $activity->getId(),
                        'type' => $activity->getType()->value,
                        'date_start' => $activity->getDateStart()->format(DATE_ATOM),
                        'date_end' => $activity->getDateEnd()->format(DATE_ATOM),
                    ],
                ];
            }
        }

        /*
         * STATISTICS
         */
        if ($withStatistics) {
            $bookings = $bookingRepository->findByClientWithActivity($client->getId());

            $years = [];

            foreach ($bookings as $booking) {
                $activity = $booking->getActivity();

                $year = (int) $activity->getDateStart()->format('Y');
                $type = $activity->getType()->value;

                $minutes = (
                    $activity->getDateEnd()->getTimestamp()
                    - $activity->getDateStart()->getTimestamp()
                ) / 60;

                if (!isset($years[$year])) {
                    $yearDto = new StatisticsByYearDto();
                    $yearDto->year = $year;
                    $years[$year] = $yearDto;
                }

                if (!isset($years[$year]->activities[$type])) {
                    $typeDto = new StatisticsByTypeDto();
                    $typeDto->activity_type = $type;
                    $typeDto->activities_count = 0;
                    $typeDto->minutes = 0;

                    $years[$year]->activities[$type] = $typeDto;
                }

                $years[$year]->activities[$type]->activities_count++;
                $years[$year]->activities[$type]->minutes += (int) $minutes;
            }

            // Reindexar arrays para JSON limpio
            foreach ($years as $yearDto) {
                $yearDto->activities = array_values($yearDto->activities);
            }

            $dto->activity_statistics = array_values($years);
        }


        return $this->json($dto, Response::HTTP_OK);
    }
}
