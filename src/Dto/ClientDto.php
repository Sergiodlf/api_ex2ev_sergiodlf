<?php

namespace App\Dto;

class ClientDto
{
    public int $id;
    public string $name;
    public string $email;
    public string $type;

    public array $activities_booked = [];
    public array $activity_statistics = [];
}
