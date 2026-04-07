<?php

namespace App\Services\Ai\Drivers;

use App\Contracts\AiProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

/**
 * OpenAI driver implementation.
 * 
 * Uses ChatGPT Vision API for image analysis.
 */
class OpenAiDriver implements AiProvider
{
    /**
     * OpenAI API key.
     *
     * @var string
     */
    protected string $apiKey;

    /**
     * OpenAI model name.
     *
     * @var string
     */
    protected string $model;

    /**
     * Create a new OpenAI driver instance.
     *
     * @param  string  $apiKey
     * @param  string  $model
     * @return void
     */
    public function __construct(string $apiKey, string $model)
    {
        $this->apiKey = $apiKey;
        $this->model = $model;
    }

    /**
     * Identify image.
     * Accepts either an UploadedFile or a public URL string.
     * For local development we may override the passed URL with a hardcoded public image URL
     * to avoid exposing locally-served URLs to external services.
     *
     * @param \Illuminate\Http\UploadedFile|string $image
     * @return array
     */
    public function identifyImage(UploadedFile|string $image): array
    {
        // If an UploadedFile was provided, create a data URL (not recommended for large images).
        if ($image instanceof UploadedFile) {
            $base64Image = base64_encode(file_get_contents($image->getRealPath()));
            $mimeType = $image->getMimeType();
        } elseif (is_string($image)) {
            // $image is expected to be a public URL (e.g., Storage::url()).
            $imageUrl = $image;
        } else {
            throw new \InvalidArgumentException('identifyImage expects UploadedFile or URL string.');
        }

        $systemPrompt = <<<'PROMPT'
    You are a highly experienced vintage goods evaluator, resale strategist, and decorative arts analyst.

    Your purpose is to help a knowledgeable buyer quickly assess photographed objects for identification, authenticity likelihood, age estimation, quality tier, retail value, resale value, and risk factors. You provide practical buy/pass guidance with realistic market awareness. You operate like a calm, precise antiques dealer combined with a pragmatic secondary-market reseller. You always try to provide links to sources or comparable items when possible to support your evaluation.

    Assume the user is experienced, efficient, and financially minded. Be concise, authoritative, and structured. Avoid fluff, hype, moralizing, or unnecessary disclaimers. Do not overstate certainty. Clearly distinguish between what is directly observable, what is probable, and what requires confirmation.

    When given an image of an object, structure your response in this exact order:

    What It Is (most likely identification)

    Era Estimate

    Construction / Material Clues

    Authenticity Tier (mass-market, studio/small batch, designer, antique, etc.)

    Value Ranges:

    Original retail (if relevant)

    Realistic resale (local marketplace / secondary market)

    Higher-tier value if branded or authenticated

    What to Check to Increase Certainty (marks, seams, hardware, underside, etc.)

    Clear Buy / Pass Recommendation with pricing thresholds

    Valuation philosophy: provide realistic secondary-market numbers. Default to conservative pricing unless branding or provenance is confirmed. Distinguish clearly between big-box decorative, boutique decorative, genuine collectible, and museum-level antique. Avoid inflated online fantasy pricing. Favor liquidity-aware pricing over aspirational pricing.

    For glass and crystal, evaluate: cut sharpness, mold seams, weight, refraction, base finish, stem quality, and maker marks. Clarify that sound/ring alone is not definitive. Distinguish pressed glass from cut crystal.

    For ceramics, evaluate: glaze type, distress vs genuine wear, foot ring finish, maker marks, production method (wheel-thrown, slip cast, molded), regional stylistic cues, and ovenproof markings when applicable.

    For wood carvings, masks, or folk art, evaluate: tool marks, interior carving texture, hardware, pigment type, patina authenticity, wear patterns, and whether it is tourist/export decor versus ethnographic artifact. Do not romanticize export craft as ceremonial art without evidence.

    When uncertainty is high, state clearly: “Cannot confirm without underside / marking / close-up.” Never fabricate maker names or specific origins.

    Use a professional, measured, dealer-to-dealer tone. No emojis. No sales hype. No exaggerated enthusiasm.

    When giving pricing guidance, use this format:

    If priced:

    Under $X → Buy

    $X–$Y → Fair

    Over $Y → Only if confirmed [condition/brand]

    Don't forget to provide the links and sources that support your evaluation whenever possible. At the end. This is crucial for the user to verify and understand the basis of your assessment.

    Primary optimization goal: help the user avoid overpaying, identify underpriced items, detect genuine upside, and avoid fantasy-value traps. 
    PROMPT;

        // Build a single text prompt that references the image URL.
        $userPrompt = <<<'PROMPT'
    Identify this inventory item from the provided image. Return a strictly valid JSON object ONLY (no markdown or surrounding text) with the following keys:
    - "title": a concise name (3-10 words)
    - "evaluation": your full evaluation
    - "description": a brief 2-3 sentence description of the item
    - "tags": an array of 3-10 short tags

    Do NOT include any explanatory text or markdown. Just emit the raw JSON object.
    PROMPT;

        // For local development we do NOT want to pass locally-hosted URLs to the external
        // OpenAI API. Override the generated/public URL with a known public image URL for testing.
        // If you want to use the real uploaded URL, replace $dummyImageUrl with $imageUrl.
        $dummyImageUrl = 'https://www.fastcar.co.uk/wp-content/uploads/sites/2/2022/10/Mini-Cooper-S-R53-1.jpg';
        $imageUrlToSend = $dummyImageUrl; // <-- swap to $imageUrl to use the real URL

        // Append the image URL so the model can access the image.
        $userPrompt .= "\n\nIMAGE_URL: {$imageUrlToSend}";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt,                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt,
                ],
            ],
            'max_completion_tokens' => 10000,
            // Some models do not support temperature=0; use default near 1.0 for compatibility
            'temperature' => 1.0,
        ]);

        if ($response->failed()) {
            throw new \Exception('OpenAI API Error: ' . $response->body());
        }

        $data = $response->json();
        error_log('OpenAI Response: ' . print_r($data, true)); // Log full response for debugging
        
        if (empty($data['choices'][0]['message']['content'])) {
             throw new \Exception('OpenAI returned no content.');
        }

        $content = $data['choices'][0]['message']['content'];

        return json_decode($content, true) ?? [];
    }

    /**
     * Perform general market analysis (Not implemented).
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     * @throws \RuntimeException
     */
    public function marketAnalysis(UploadedFile|string $image): array
    {
        throw new \RuntimeException('Market analysis is not yet implemented for the OpenAI driver.');
    }

    /**
     * Perform Facebook Marketplace specific analysis (Not implemented).
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     * @throws \RuntimeException
     */
    public function facebookAnalysis(UploadedFile|string $image): array
    {
        throw new \RuntimeException('Facebook analysis is not yet implemented for the OpenAI driver.');
    }

    /**
     * Perform Etsy specific analysis (Not implemented).
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     * @throws \RuntimeException
     */
    public function etsyAnalysis(UploadedFile|string $image): array
    {
        throw new \RuntimeException('Etsy analysis is not yet implemented for the OpenAI driver.');
    }

    /**
     * Scan image for multiple items (Not implemented).
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array
     * @throws \RuntimeException
     */
    public function shotgunScan(UploadedFile|string $image): array
    {
        throw new \RuntimeException('Shotgun scan is not yet implemented for the OpenAI driver.');
    }
}
