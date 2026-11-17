<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

trait ReadsDesignData
{
    /** Reads .claude/design/data/{file} as an array. */
    protected function designData(string $file): array
    {
        $path = base_path('.claude/design/data/'.$file);
        if (! is_file($path)) {
            throw new RuntimeException("Seed data file not found: {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
