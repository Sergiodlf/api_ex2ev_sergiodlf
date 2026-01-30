<?php

namespace App\Controller;

use App\Dto\ActivityDto;
use App\Dto\ActivityListDto;
use App\Repository\ActivityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ActivityController extends AbstractController
{
    #[Route('/activities', methods: ['GET'])]
    public function list(
        Request $request,
        ActivityRepository $activityRepository
    ): JsonResponse {
        $type = $request->query->get('type');
        $onlyFree = filter_var(
            $request->query->get('onlyfree'),
            FILTER_VALIDATE_BOOLEAN
        );

        $page = max(1, (int) $request->query->get('page', 1));
        $pageSize = max(1, (int) $request->query->get('page_size', 10));
        $order = $request->query->get('order', 'asc');

        $results = $activityRepository->findForListing(
            $type,
            $onlyFree,
            $page,
            $pageSize,
            $order
        );

        $listDto = new ActivityListDto();
        $listDto->meta = [[
            'page' => $page,
            'limit' => $pageSize,
            'total-items' => count($results),
        ]];


        foreach ($results as $row) {
            $activity = $row[0];
            $signed = (int) $row['signed'];

            $dto = new ActivityDto();
            $dto->id = $activity->getId();
            $dto->max_participants = $activity->getMaxParticipants();
            $dto->clients_signed = (int) $signed;
            $dto->type = $activity->getType()->value;
            $dto->date_start = $activity->getDateStart()->format(DATE_ATOM);
            $dto->date_end = $activity->getDateEnd()->format(DATE_ATOM);

            // play_list (1–M Song)
            foreach ($activity->getSongs() as $song) {
                $dto->play_list[] = [
                    'id' => $song->getId(),
                    'name' => $song->getName(),
                    'duration_seconds' => $song->getDurationSeconds(),
                ];
            }

            $listDto->data[] = $dto;
        }

        return $this->json($listDto);
    }
}
