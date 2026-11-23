<?php

namespace Database\Seeders\Concerns;

use RuntimeException;

trait ReadsDesignData
{
    /**
     * Reads database/seeders/data/{file} as an array. The files are price-free copies of the
     * prototype's data (project rule: no prices anywhere), committed so they deploy with the code.
     */
    protected function designData(string $file): array
    {
        $path = database_path('seeders/data/'.$file);
        if (! is_file($path)) {
            throw new RuntimeException("Seed data file not found: {$path}");
        }

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
