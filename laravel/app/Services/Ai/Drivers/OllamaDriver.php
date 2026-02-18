<?php

namespace App\Services\Ai\Drivers;

use App\Contracts\AiProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class OllamaDriver implements AiProvider
{
    protected string $baseUrl;
    protected string $model;

    public function __construct(string $baseUrl, string $model)
    {
        $this->baseUrl = $baseUrl;
        $this->model = $model;
    }

    public function identifyImage(UploadedFile $image): array
    {
        $base64Image = base64_encode(file_get_contents($image->getRealPath()));

        $response = Http::post("{$this->baseUrl}/api/generate", [
            'model' => $this->model,
            'prompt' => "Identify this inventory item. Return a JSON object with 'title' (3-10 words), 'description' (1-2 sentences), and 'tags' (array of 3-5 strings). Respond ONLY with valid JSON.",
            'images' => [$base64Image],
            'stream' => false,
            'format' => 'json',
        ]);

        if ($response->failed()) {
            throw new \Exception('Ollama API Error: ' . $response->body());
        }

        $data = $response->json();
        
        if (empty($data['response'])) {
             throw new \Exception('Ollama returned no response.');
        }

        return json_decode($data['response'], true) ?? [];
    }
}
