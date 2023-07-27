<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

abstract class Service
{
    abstract public function create(array $request, string $client = null): Model;

    protected function getDataByKey(array $request, string $key): ?string
    {
        if (Arr::get($request, "properties.{$key}.0.html")) {
            return Arr::get($request, "properties.{$key}.0.html");
        }

        if (is_string(Arr::get($request, "properties.{$key}.0"))) {
            return Arr::get($request, "properties.{$key}.0");
        }

        if (is_string(Arr::get($request, "properties.{$key}"))) {
            return Arr::get($request, "properties.{$key}");
        }

        return Arr::get($request, $key);
    }
}
