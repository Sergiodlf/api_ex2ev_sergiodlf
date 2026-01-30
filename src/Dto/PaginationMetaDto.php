<?php

namespace App\Dto;

class PaginationMetaDto
{
    public int $page;
    public int $page_size;
    public int $total_items;
    public int $total_pages;
}
