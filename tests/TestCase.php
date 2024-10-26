<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    public function removeDirIfEmpty(string $dir): void
    {
        // scandir() will always return `.` and `..` so even an “empty”
        // directory will have a count of 2
        if (is_dir($dir) && count(scandir($dir)) === 2) {
            rmdir($dir);
        }
    }
}
