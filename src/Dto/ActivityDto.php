<?php

namespace App\Dto;

class ActivityDto
{
    public int $id;
    public int $max_participants;
    public int $clients_signed;
    public string $type;
    public array $play_list = [];
    public string $date_start;
    public string $date_end;
}
