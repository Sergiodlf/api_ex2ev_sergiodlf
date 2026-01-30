<?php

namespace App\Dto;

class ClientDto
{
    public int $id;
    public string $type;
    public string $name;
    public string $email;

    public array $activities_booked = [];
    public array $activity_statistics = [];
}
