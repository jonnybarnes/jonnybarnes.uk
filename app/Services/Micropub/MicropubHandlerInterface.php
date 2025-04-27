<?php

declare(strict_types=1);

namespace App\Services\Micropub;

interface MicropubHandlerInterface
{
    public function handle(array $data);
}
