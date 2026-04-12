<?php

namespace Tests\Unit;

use App\Contracts\AiProvider;
use App\Services\Ai\AiManager;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class AiManagerTest extends TestCase
{
    public function test_it_delegates_to_the_default_driver()
    {
        $mockDriver = Mockery::mock(AiProvider::class);
        $mockDriver->shouldReceive('identifyImage')
            ->once()
            ->with('test-image')
            ->andReturn(['title' => 'Mocked Item']);

        $manager = new AiManager($this->app);
        $manager->extend('mock', function () use ($mockDriver) {
            return $mockDriver;
        });

        Config::set('ai.default', 'mock');

        $result = $manager->identifyImage('test-image');

        $this->assertEquals(['title' => 'Mocked Item'], $result);
    }

    public function test_it_delegates_shotgun_scan()
    {
        $mockDriver = Mockery::mock(AiProvider::class);
        $mockDriver->shouldReceive('shotgunScan')
            ->once()
            ->with('test-image')
            ->andReturn([['title' => 'Item 1'], ['title' => 'Item 2']]);

        $manager = new AiManager($this->app);
        $manager->extend('mock', function () use ($mockDriver) {
            return $mockDriver;
        });

        Config::set('ai.default', 'mock');

        $result = $manager->shotgunScan('test-image');

        $this->assertCount(2, $result);
    }
}
