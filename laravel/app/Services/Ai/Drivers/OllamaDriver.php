<?php

namespace App\Services\Ai\Drivers;

use App\Contracts\AiProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class OllamaDriver implements AiProvider
{
    /**
     * Ollama base URL
     *
     * @var string
     */
    protected string $baseUrl;

    /**
     * Ollama model name
     *
     * @var string
     */
    protected string $model;

    /**
     * Create a new Ollama driver instance
     *
     * @param  string  $baseUrl  The Ollama server base URL
     * @param  string  $model    The Ollama model to use
     */
    public function __construct(string $baseUrl, string $model)
    {
        $this->baseUrl = $baseUrl;
        $this->model = $model;
    }

    /**
     * Identify image and extract metadata using Ollama API
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array  Array with 'title', 'description', 'tags' keys
     * @throws \RuntimeException
     */
    public function identifyImage(UploadedFile|string $image): array
    {
        try {
            if ($image instanceof UploadedFile) {
                $base64Image = base64_encode(file_get_contents($image->getRealPath()));
            } else {
                // If it's a URL, we need to fetch the image content
                $response = Http::get($image);
                if ($response->failed()) {
                    throw new \RuntimeException("Failed to fetch image from URL: {$image}");
                }
                $base64Image = base64_encode($response->body());
            }

            $response = Http::timeout(60)->post("{$this->baseUrl}/api/generate", [
                'model' => $this->model,
                'prompt' => "Identify this inventory item. Return a JSON object with 'title' (3-10 words), 'description' (1-2 sentences), and 'tags' (array of 3-5 strings). Respond ONLY with valid JSON.",
                'images' => [$base64Image],
                'stream' => false,
                'format' => 'json',
            ]);

            if ($response->failed()) {
                throw new \RuntimeException("Ollama API Error: {$response->body()}");
            }

            $data = $response->json();
            
            if (empty($data['response'])) {
                throw new \RuntimeException('Ollama returned no response.');
            }

            $result = json_decode($data['response'], true);
            if (!is_array($result)) {
                throw new \RuntimeException('Invalid JSON response from Ollama');
            }

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Image identification failed: {$e->getMessage()}");
        }
    }

    public function marketAnalysis(UploadedFile|string $image): array
    {
        throw new \RuntimeException('Market analysis is not yet implemented for the Ollama driver.');
    }

    public function facebookAnalysis(UploadedFile|string $image): array
    {
        throw new \RuntimeException('Facebook analysis is not yet implemented for the Ollama driver.');
    }

    public function etsyAnalysis(UploadedFile|string $image): array
    {
        throw new \RuntimeException('Etsy analysis is not yet implemented for the Ollama driver.');
    }
}
