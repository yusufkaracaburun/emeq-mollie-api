<?php

declare(strict_types=1);

use Emeq\MollieApi\Tests\TestCase;
use Illuminate\Support\Facades\Cache;

uses(TestCase::class)->in(__DIR__);

// Laravel's array cache persists within the Testbench process, so a prior
// test can poison cache state for the next test. Flushing here keeps each
// test hermetic — same pattern as the Snelstart SDK.
uses()->beforeEach(function (): void {
    Cache::flush();
})->in(__DIR__);
