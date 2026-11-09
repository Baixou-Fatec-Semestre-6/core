<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        if (
            getenv('DB_CONNECTION') !== 'sqlite'
            || getenv('DB_DATABASE') !== ':memory:'
            || getenv('DB_URL') !== ''
        ) {
            throw new RuntimeException('Tests must use the isolated SQLite in-memory database.');
        }

        parent::setUp();
    }
}
