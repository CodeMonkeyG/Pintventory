<?php

namespace App\Services\Ai;

use Illuminate\Support\Manager;
use App\Contracts\AiProvider;
use App\Services\Ai\Drivers\GeminiDriver;
use App\Services\Ai\Drivers\OpenAiDriver;
use App\Services\Ai\Drivers\OllamaDriver;

class AiManager extends Manager implements AiProvider
{
    public function getDefaultDriver()
    {
        return $this->config->get('ai.default');
    }

    protected function createGeminiDriver()
    {
        $config = $this->config->get('ai.drivers.gemini');
        return new GeminiDriver($config['api_key'], $config['model']);
    }

    // Placeholders for other drivers
    protected function createOpenAiDriver()
    {
        $config = $this->config->get('ai.drivers.openai');
        return new OpenAiDriver($config['api_key'], $config['model']);
    }

    protected function createOllamaDriver()
    {
        $config = $this->config->get('ai.drivers.ollama');
        return new OllamaDriver($config['base_url'], $config['model']);
    }

    public function identifyImage(\Illuminate\Http\UploadedFile|string $image): array
    {
        return $this->driver()->identifyImage($image);
    }
}
