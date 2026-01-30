<?php

namespace App\Dto;

class StatisticsByYearDto
{
    public int $year;

    /** @var StatisticsByTypeDto[] */
    public array $activities = [];
}
