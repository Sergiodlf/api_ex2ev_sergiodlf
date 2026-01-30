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
        $dto->type = $client->getType()->value;
        $dto->name = $client->getName();
        $dto->email = $client->getEmail();

        /*
         * BOOKINGS
         */
        if ($withBookings) {
            foreach ($client->getBookings() as $booking) {
                $activity = $booking->getActivity();

                // clients_signed
                $clientsSigned = count($activity->getBookings());

                // play_list
                $playList = [];
                foreach ($activity->getSongs() as $song) {
                    $playList[] = [
                        'id' => $song->getId(),
                        'name' => $song->getName(),
                        'duration_seconds' => $song->getDurationSeconds(),
                    ];
                }

                $dto->activities_booked[] = [
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
                    $years[$year] = [
                        'year' => $year,
                        'statistics_by_type' => []
                    ];
                }

                if (!isset($years[$year]['statistics_by_type'][$type])) {
                    $years[$year]['statistics_by_type'][$type] = [
                        'type' => $type,
                        'statistics' => [[
                            'num_activities' => '0',
                            'num_minutes' => '0',
                        ]]
                    ];
                }

                $years[$year]['statistics_by_type'][$type]['statistics'][0]['num_activities'] =
                    (string) ((int) $years[$year]['statistics_by_type'][$type]['statistics'][0]['num_activities'] + 1);

                $years[$year]['statistics_by_type'][$type]['statistics'][0]['num_minutes'] =
                    (string) ((int) $years[$year]['statistics_by_type'][$type]['statistics'][0]['num_minutes'] + (int) $minutes);
            }

            // Reindexar
            foreach ($years as &$yearData) {
                $yearData['statistics_by_type'] = array_values($yearData['statistics_by_type']);
            }

            $dto->activity_statistics = array_values($years);
        }


        return $this->json($dto, Response::HTTP_OK);
    }
}
