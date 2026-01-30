<?php

namespace App\Dto;

class ActivityListDto
{
    /** @var ActivityDto[] */
    public array $data = [];

    public PaginationMetaDto $meta;
}
