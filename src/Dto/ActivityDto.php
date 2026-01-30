<?php

namespace App\Dto;

class ActivityDto
{
    public int $id;
    public string $type;
    public int $max_participants;
    public int $clients_signed;
    public array $play_list = [];
    public string $date_start;
    public string $date_end;
}
