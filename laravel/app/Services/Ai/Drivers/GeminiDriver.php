<?php

namespace App\Services\Ai\Drivers;

use App\Contracts\AiProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * Google Gemini AI driver implementation.
 * 
 * Handles multi-modal content generation for image identification and market research.
 */
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
        $imageData = $this->processImage($image);
        $base64Image = $imageData['base64'];
        $mimeType = $imageData['mimeType'];

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $systemPrompt = <<<'PROMPT'
You are a highly experienced vintage goods evaluator, resale strategist, and decorative arts analyst.
Your purpose is to help a knowledgeable buyer quickly assess photographed objects for identification, authenticity likelihood, age estimation, quality tier, retail value, resale value, and risk factors. You provide practical buy/pass guidance with realistic market awareness.

When given an image of an object, you must provide a detailed evaluation.
PROMPT;

            $prompt = $systemPrompt . "\n\nIdentify this inventory item. Return a JSON object with: 
        - 'title': a concise name (3-10 words).
        - 'description': a short description (1-2 sentences).
        - 'item_type': 'unique' if it's a one-of-a-kind, vintage, or rare item that will likely only have 1 in stock and not be reordered. 'standard' if it's a modern, mass-produced item that could have quantity and be reordered.
        - 'evaluation': a detailed evaluation including era, material, and value estimation as a plain text block.
        - 'tags': an array of 3-10 tags.
        - 'market_analysis': an object containing price ranges and ideal search queries for:
            - 'ebay': { 'range': string, 'query': string }
            - 'facebook': { 'range': string, 'query': string }
            - 'offerup': { 'range': string, 'query': string }
            - 'etsy': { 'range': string, 'query': string }";

            return $this->callGemini($url, $prompt, $base64Image, $mimeType, [
                'type' => 'object',
                'properties' => [
                    'title' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'item_type' => [
                        'type' => 'string',
                        'enum' => ['unique', 'standard']
                    ],
                    'evaluation' => ['type' => 'string'],
                    'tags' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'market_analysis' => [
                        'type' => 'object',
                        'properties' => [
                            'ebay' => [
                                'type' => 'object',
                                'properties' => [
                                    'range' => ['type' => 'string'],
                                    'query' => ['type' => 'string']
                                ],
                                'required' => ['range', 'query']
                            ],
                            'facebook' => [
                                'type' => 'object',
                                'properties' => [
                                    'range' => ['type' => 'string'],
                                    'query' => ['type' => 'string']
                                ],
                                'required' => ['range', 'query']
                            ],
                            'offerup' => [
                                'type' => 'object',
                                'properties' => [
                                    'range' => ['type' => 'string'],
                                    'query' => ['type' => 'string']
                                ],
                                'required' => ['range', 'query']
                            ],
                            'etsy' => [
                                'type' => 'object',
                                'properties' => [
                                    'range' => ['type' => 'string'],
                                    'query' => ['type' => 'string']
                                ],
                                'required' => ['range', 'query']
                            ]
                        ],
                        'required' => ['ebay', 'facebook', 'offerup', 'etsy']
                    ]
                ],
                'required' => ['title', 'description', 'item_type', 'evaluation', 'tags', 'market_analysis']
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException("Image identification failed: {$e->getMessage()}");
        }
    }

    /**
     * Perform market analysis using Gemini API
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     * @throws \RuntimeException
     */
    public function marketAnalysis(UploadedFile|string $image): array
    {
        $imageData = $this->processImage($image);
        $base64Image = $imageData['base64'];
        $mimeType = $imageData['mimeType'];

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $systemPrompt = <<<'PROMPT'
You are a thrift shop flipper and eBay resale expert. Your goal is to provide a "Flipping Strategy" for an item based on its image.
Analyze the market (simulating a check of eBay/sold listings) and provide:
1. Estimated "Listing Price" range (Active listings).
2. Estimated "Sold Price" range (Actual completed sales).
3. "Sell-through Rate" (High/Medium/Low) based on how many seem to be selling vs listed.
4. "Keywords" to use in an eBay title for maximum visibility.
5. "Flipping Advice": Specific tips for a reseller (e.g., "Look for X flaw", "Ship via Y method").
PROMPT;

            $prompt = $systemPrompt . "\n\nAnalyze the market for this item. Return a JSON object with: 
        - 'listing_price_range': string (e.g. '$20 - $35').
        - 'sold_price_range': string (e.g. '$15 - $25').
        - 'sell_through_rate': string (e.g. 'High - 75%').
        - 'suggested_ebay_title': string.
        - 'flipping_advice': detailed text block.
        - 'listing_copy': a ready-to-paste listing description block including title, condition notes, and key features.
        - 'market_url': a direct URL to eBay search results for similar items (active or sold).";

            return $this->callGemini($url, $prompt, $base64Image, $mimeType, [
                'type' => 'object',
                'properties' => [
                    'listing_price_range' => ['type' => 'string'],
                    'sold_price_range' => ['type' => 'string'],
                    'sell_through_rate' => ['type' => 'string'],
                    'suggested_ebay_title' => ['type' => 'string'],
                    'flipping_advice' => ['type' => 'string'],
                    'listing_copy' => ['type' => 'string'],
                    'market_url' => ['type' => 'string']
                ],
                'required' => ['listing_price_range', 'sold_price_range', 'sell_through_rate', 'suggested_ebay_title', 'flipping_advice', 'listing_copy', 'market_url']
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException("Market analysis failed: {$e->getMessage()}");
        }
    }

    /**
     * Perform Facebook Marketplace analysis using Gemini API
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     * @throws \RuntimeException
     */
    public function facebookAnalysis(UploadedFile|string $image): array
    {
        $imageData = $this->processImage($image);
        $base64Image = $imageData['base64'];
        $mimeType = $imageData['mimeType'];

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $systemPrompt = <<<'PROMPT'
You are a Facebook Marketplace and Buy/Sell Group resale expert. Your goal is to provide a "Facebook Selling Strategy" for an item based on its image.
Analyze the local market trends and provide:
1. Estimated "Marketplace Price" (Local cash price).
2. "Target Audience": Who buys this on Facebook? (e.g., "Moms/Parents", "Mid-Century Modern Collectors").
3. "Relevant Groups": Types of Facebook groups to post in (e.g., "Local Free/Swap", "Vintage Furniture NYC").
4. "Safety & Scams": Specific things to watch for with this item (e.g., "Common Zelle scam", "Meet at police station for high-value tech").
5. "Listing Tips": Specific keywords and hooks to use for a quick local sale.
PROMPT;

            $prompt = $systemPrompt . "\n\nAnalyze the Facebook market for this item. Return a JSON object with: 
        - 'local_price_estimate': string (e.g. '$40 - $60').
        - 'target_audience': string.
        - 'suggested_groups': array of strings.
        - 'safety_tips': string.
        - 'listing_strategy': detailed text block.
        - 'listing_copy': a ready-to-paste FB Marketplace description including title and pickup/safety info.
        - 'market_url': a direct URL to Facebook Marketplace search for this item.";

            return $this->callGemini($url, $prompt, $base64Image, $mimeType, [
                'type' => 'object',
                'properties' => [
                    'local_price_estimate' => ['type' => 'string'],
                    'target_audience' => ['type' => 'string'],
                    'suggested_groups' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'safety_tips' => ['type' => 'string'],
                    'listing_strategy' => ['type' => 'string'],
                    'listing_copy' => ['type' => 'string'],
                    'market_url' => ['type' => 'string']
                ],
                'required' => ['local_price_estimate', 'target_audience', 'suggested_groups', 'safety_tips', 'listing_strategy', 'listing_copy', 'market_url']
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException("Facebook analysis failed: {$e->getMessage()}");
        }
    }

    /**
     * Perform Etsy market analysis using Gemini API
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     * @throws \RuntimeException
     */
    public function etsyAnalysis(UploadedFile|string $image): array
    {
        $imageData = $this->processImage($image);
        $base64Image = $imageData['base64'];
        $mimeType = $imageData['mimeType'];

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

            $systemPrompt = <<<'PROMPT'
You are an Etsy vintage and handmade goods expert. Your goal is to provide an "Etsy Selling Strategy" for an item based on its image.
Analyze the global Etsy market trends for vintage or specialized items and provide:
1. Estimated "Etsy Price Range" (Considering the "Etsy premium" for curation).
2. "Target Persona": Who is the typical Etsy buyer for this? (e.g., "Boho Home Decorators", "Vintage Jewelry Enthusiasts").
3. "Key Tags/Keywords": 13 SEO-optimized tags for Etsy (as a list).
4. "Shipping Strategy": How to safely ship this globally (e.g., "Double box required", "Fits in small padded mailer").
5. "Curation Advice": How to style or photograph this item for Etsy's aesthetic.
PROMPT;

            $prompt = $systemPrompt . "\n\nAnalyze the Etsy market for this item. Return a JSON object with: 
        - 'etsy_price_estimate': string (e.g. '$65 - $85').
        - 'target_persona': string.
        - 'seo_tags': array of 13 strings.
        - 'shipping_advice': string.
        - 'curation_strategy': detailed text block.
        - 'listing_copy': a ready-to-paste Etsy listing description including title, story, and materials.
        - 'market_url': a direct URL to Etsy search for similar vintage items.";

            return $this->callGemini($url, $prompt, $base64Image, $mimeType, [
                'type' => 'object',
                'properties' => [
                    'etsy_price_estimate' => ['type' => 'string'],
                    'target_persona' => ['type' => 'string'],
                    'seo_tags' => [
                        'type' => 'array',
                        'items' => ['type' => 'string']
                    ],
                    'shipping_advice' => ['type' => 'string'],
                    'curation_strategy' => ['type' => 'string'],
                    'listing_copy' => ['type' => 'string'],
                    'market_url' => ['type' => 'string']
                ],
                'required' => ['etsy_price_estimate', 'target_persona', 'seo_tags', 'shipping_advice', 'curation_strategy', 'listing_copy', 'market_url']
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException("Etsy analysis failed: {$e->getMessage()}");
        }
    }

    /**
     * Process image to base64 and mime type
     */
    private function processImage(UploadedFile|string $image): array
    {
        if ($image instanceof UploadedFile) {
            $base64Image = base64_encode(file_get_contents($image->getRealPath()));
            $mimeType = $image->getMimeType();
        } else {
            $response = Http::get($image);
            if ($response->failed()) {
                throw new \RuntimeException("Failed to fetch image from URL: {$image}");
            }
            $base64Image = base64_encode($response->body());
            $mimeType = $response->header('Content-Type');
        }
        return ['base64' => $base64Image, 'mimeType' => $mimeType];
    }

    /**
     * Common method to call Gemini API
     */
    private function callGemini(string $url, string $prompt, string $base64Image, string $mimeType, array $schema): array
    {
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
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'response_schema' => $schema
            ]
        ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? $response->body();
            throw new \RuntimeException("Gemini API Error: {$errorMsg}");
        }

        $data = $response->json();
        if (empty($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \RuntimeException('Gemini returned no content.');
        }

        $result = json_decode($data['candidates'][0]['content']['parts'][0]['text'], true);
        if (!is_array($result)) {
            throw new \RuntimeException('Invalid JSON response from Gemini');
        }

        return $result;
    }
}