<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

enum SqlMode
{
    case MySQL;
    case ANSI;

    public function getStartQoute(): string
    {
        return match ($this) {
            SqlMode::MySQL => '`',
            SqlMode::ANSI => '"',
        };
    }

    public function getEndQoute(): string
    {
        return match ($this) {
            SqlMode::MySQL => '`',
            SqlMode::ANSI => '"',
        };
    }
}
