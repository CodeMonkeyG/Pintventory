<?php

namespace App\Services\Ai\Drivers;

use App\Contracts\AiProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class GeminiDriver implements AiProvider
{
    protected string $apiKey;
    protected string $model;

    public function __construct(string $apiKey, string $model)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    public function identifyImage(UploadedFile $image): array
    {
        $base64Image = base64_encode(file_get_contents($image->getRealPath()));
        $mimeType = $image->getMimeType();

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $prompt = "Identify this inventory item. Return a JSON object with: 
        - 'title': a concise name (3-10 words).
        - 'description': a short description (1-2 sentences).
        - 'tags': an array of 3-5 tags.
        Do not include markdown formatting like ```json ... ```. Just the raw JSON string.";

        $response = Http::post($url, [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Image
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        if ($response->failed()) {
            throw new \Exception('Gemini API Error: ' . $response->body());
        }

        $data = $response->json();
        
        // Safety check for empty response
        if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
             throw new \Exception('Gemini returned no content.');
        }

        $text = $data['candidates'][0]['content']['parts'][0]['text'];
        
        // Clean up markdown code blocks if present (Gemini loves them)
        $text = preg_replace('/^```json\s*|```\s*$/', '', trim($text));

        return json_decode($text, true) ?? [];
    }
}
