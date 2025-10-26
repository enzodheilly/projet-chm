<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FileSizeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('human_size', [$this, 'formatBytes']),
        ];
    }

    public function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' o';
        $units = ['Ko', 'Mo', 'Go', 'To'];
        $i = floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i - 1];
    }
}
