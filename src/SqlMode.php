<?php

declare(strict_types=1);

namespace Dschledermann\Dto;

enum SqlMode
{
    case MySQL;
    case ANSI;

    public function qouteName(string $name): string
    {
        return match($this) {
            SqlMode::MySQL => '`' . $name . '`',
            default => '"' . $name . '"',
        };
    }
}
