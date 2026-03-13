<?php

declare(strict_types=1);

namespace App\Services\Micropub\Data;

use Illuminate\Http\Request;

abstract class MicropubData
{
    abstract public static function fromRequest(Request $request): static;

    abstract public static function fromArray(array $data): static;

    abstract public function toArray(): array;
}
