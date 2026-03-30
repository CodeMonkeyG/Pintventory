<?php

namespace App\Services\Ai\Drivers;

use App\Contracts\AiProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class GeminiDriver implements AiProvider
{
    /**
     * Gemini API key
     *
     * @var string
     */
    protected string $apiKey;

    /**
     * Gemini model name
     *
     * @var string
     */
    protected string $model;

    /**
     * Create a new Gemini driver instance
     *
     * @param  string  $apiKey  The Gemini API key
     * @param  string  $model   The Gemini model to use
     */
    public function __construct(string $apiKey, string $model)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Identify image and extract metadata using Gemini API
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
                $mimeType = $image->getMimeType();
            } else {
                // If it's a URL, we need to fetch the image content
                // For local development, we might use a dummy image if the URL is not accessible
                $imageUrl = $image;
                
                // If the URL contains 'pintventory', it might be a local URL not accessible from the internet
                // but since we are running in the same network or fetching it ourselves, it's fine.
                // However, let's follow the OpenAI driver's lead if needed.
                
                $response = Http::get($imageUrl);
                if ($response->failed()) {
                    throw new \RuntimeException("Failed to fetch image from URL: {$imageUrl}");
                }
                
                $base64Image = base64_encode($response->body());
                $mimeType = $response->header('Content-Type');
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $systemPrompt = <<<'PROMPT'
You are a highly experienced vintage goods evaluator, resale strategist, and decorative arts analyst.
Your purpose is to help a knowledgeable buyer quickly assess photographed objects for identification, authenticity likelihood, age estimation, quality tier, retail value, resale value, and risk factors. You provide practical buy/pass guidance with realistic market awareness.

When given an image of an object, you must provide a detailed evaluation.
PROMPT;

            $prompt = $systemPrompt . "\n\nIdentify this inventory item. Return a JSON object with: 
        - 'title': a concise name (3-10 words).
        - 'description': a short description (1-2 sentences).
        - 'evaluation': a detailed evaluation including era, material, and value estimation.
        - 'tags': an array of 3-10 tags.
        Do not include markdown formatting like ```json ... ```. Just the raw JSON string.";

            $response = Http::timeout(30)->post($url, [
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
                $errorMsg = $response->json('error.message') ?? $response->body();
                throw new \RuntimeException("Gemini API Error: {$errorMsg}");
            }

            $data = $response->json();
            
            // Safety check for empty response
            if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
                throw new \RuntimeException('Gemini returned no content.');
            }

            $text = $data['candidates'][0]['content']['parts'][0]['text'];
            
            // Clean up markdown code blocks if present
            $text = preg_replace('/^```json\s*|```\s*$/', '', trim($text));

            $result = json_decode($text, true);
            if (!is_array($result)) {
                throw new \RuntimeException('Invalid JSON response from Gemini');
            }

            return $result;
        } catch (\Exception $e) {
            throw new \RuntimeException("Image identification failed: {$e->getMessage()}");
        }
    }
}
