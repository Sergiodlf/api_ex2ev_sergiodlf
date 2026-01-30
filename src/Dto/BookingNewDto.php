<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class BookingNewDto
{
    #[Assert\NotNull]
    #[Assert\Positive]
    public int $activity_id;

    #[Assert\NotNull]
    #[Assert\Positive]
    public int $client_id;
}
