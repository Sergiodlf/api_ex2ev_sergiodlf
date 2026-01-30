<?php

namespace App\Controller;

use App\Dto\ActivityDto;
use App\Dto\ActivityListDto;
use App\Dto\PaginationMetaDto;
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

        $meta = new PaginationMetaDto();
        $meta->page = $page;
        $meta->page_size = $pageSize;
        $meta->total_items = count($results);
        $meta->total_pages = 1;

        $listDto->meta = $meta;

        foreach ($results as $row) {
            $activity = $row[0];
            $signed = (int) $row['signed'];
            
            $dto = new ActivityDto();
            $dto->id = $activity->getId();
            $dto->type = $activity->getType()->value;
            $dto->max_participants = $activity->getMaxParticipants();
            $dto->clients_signed = (int) $signed;
            $dto->date_start = $activity->getDateStart()->format(DATE_ATOM);
            $dto->date_end = $activity->getDateEnd()->format(DATE_ATOM);

            // play_list (1–M Song)
            foreach ($activity->getSongs() as $song) {
                $dto->play_list[] = [
                    'title' => $song->getTitle(),
                    'artist' => $song->getArtist(),
                ];
            }

            $listDto->data[] = $dto;
        }

        return $this->json($listDto);
    }
}
