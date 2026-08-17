<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

class SidebarIcons
{
    /** @var list<string>|null */
    protected static ?array $cache = null;

    protected static function normalize(string $filename): string
    {
        if (str_ends_with($filename, '.blade.php')) {
            return substr($filename, 0, -10);
        }

        return pathinfo($filename, PATHINFO_FILENAME);
    }

    /** @return list<string> */
    public static function all(): array
    {
        if (static::$cache !== null) {
            return static::$cache;
        }

        $icons = [];

        $stubDir = base_path('vendor/livewire/flux/stubs/resources/views/flux/icon');
        $customDir = resource_path('views/flux/icon');

        if (File::isDirectory($stubDir)) {
            foreach (File::files($stubDir) as $file) {
                $name = static::normalize($file->getFilename());

                if ($name !== 'index') {
                    $icons[] = $name;
                }
            }
        }

        if (File::isDirectory($customDir)) {
            foreach (File::files($customDir) as $file) {
                $name = static::normalize($file->getFilename());

                if ($name !== 'index') {
                    $icons[] = $name;
                }
            }
        }

        $icons = array_values(array_unique($icons));
        sort($icons);

        return static::$cache = $icons;
    }

    public static function isValid(?string $icon): bool
    {
        if (! filled($icon)) {
            return false;
        }

        return in_array($icon, static::all(), true);
    }
}
