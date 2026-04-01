<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

/**
 * Interface for AI providers that handle image analysis and market research.
 */
interface AiProvider
{
    /**
     * Identify an item from an image and extract basic metadata.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array  Array containing 'title', 'description', 'tags', etc.
     */
    public function identifyImage(UploadedFile|string $image): array;

    /**
     * Perform general market analysis for an item image.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array  Array containing price ranges, sell-through rate, and flipping advice.
     */
    public function marketAnalysis(UploadedFile|string $image): array;

    /**
     * Perform Facebook Marketplace specific analysis.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array  Array containing local price estimates, target audience, and listing strategy.
     */
    public function facebookAnalysis(UploadedFile|string $image): array;

    /**
     * Perform Etsy specific analysis for vintage/specialized items.
     *
     * @param  \Illuminate\Http\UploadedFile|string  $image
     * @return array  Array containing Etsy price estimates, SEO tags, and curation strategy.
     */
    public function etsyAnalysis(UploadedFile|string $image): array;
}
