<?php

namespace App\Services\Ai;

use Illuminate\Support\Manager;
use App\Contracts\AiProvider;
use App\Services\Ai\Drivers\GeminiDriver;
use App\Services\Ai\Drivers\OpenAiDriver;
use App\Services\Ai\Drivers\OllamaDriver;

/**
 * Manager class for handling multiple AI providers.
 * 
 * Extends Laravel's Manager to provide a common interface for different AI drivers.
 */
class AiManager extends Manager implements AiProvider
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('ai.default');
    }

    /**
     * Create a Gemini AI driver instance.
     *
     * @return \App\Services\Ai\Drivers\GeminiDriver
     */
    protected function createGeminiDriver()
    {
        $config = $this->config->get('ai.drivers.gemini');
        return new GeminiDriver($config['api_key'], $config['model']);
    }

    /**
     * Create an OpenAI driver instance.
     *
     * @return \App\Services\Ai\Drivers\OpenAiDriver
     */
    protected function createOpenAiDriver()
    {
        $config = $this->config->get('ai.drivers.openai');
        return new OpenAiDriver($config['api_key'], $config['model']);
    }

    /**
     * Create an Ollama driver instance.
     *
     * @return \App\Services\Ai\Drivers\OllamaDriver
     */
    protected function createOllamaDriver()
    {
        $config = $this->config->get('ai.drivers.ollama');
        return new OllamaDriver($config['base_url'], $config['model']);
    }

    /**
     * Identify an item from an image using the default driver.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     */
    public function identifyImage(\Illuminate\Http\UploadedFile|string $image): array
    {
        return $this->driver()->identifyImage($image);
    }

    /**
     * Perform general market analysis using the default driver.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     */
    public function marketAnalysis(\Illuminate\Http\UploadedFile|string $image): array
    {
        return $this->driver()->marketAnalysis($image);
    }

    /**
     * Perform Facebook Marketplace specific analysis using the default driver.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     */
    public function facebookAnalysis(\Illuminate\Http\UploadedFile|string $image): array
    {
        return $this->driver()->facebookAnalysis($image);
    }

    /**
     * Perform Etsy specific analysis using the default driver.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     */
    public function etsyAnalysis(\Illuminate\Http\UploadedFile|string $image): array
    {
        return $this->driver()->etsyAnalysis($image);
    }
}
