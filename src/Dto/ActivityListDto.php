<?php

namespace App\Dto;

class ActivityListDto
{
    /** @var ActivityDto[] */
    public array $data = [];

    /**
     * Meta information for pagination
     * Structure defined by OpenAPI (array with page, limit, total-items)
     *
     * @var array<int, array{
     *     page: int,
     *     limit: int,
     *     total-items: int
     * }>
     */
    public array $meta = [];
}
