<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\AiProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Controller for handling AI-powered image analysis and market research.
 */
class AiController extends Controller
{
    /**
     * The AI provider instance.
     *
     * @var \App\Contracts\AiProvider
     */
    protected $ai;

    /**
     * Create a new controller instance.
     *
     * @param  \App\Contracts\AiProvider  $ai
     * @return void
     */
    public function __construct(AiProvider $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Identify an item from an uploaded image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function identify(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB
        ]);

        try {
            // Pass the UploadedFile object directly to the AI driver.
            $file = $request->file('image');
            $result = $this->ai->identifyImage($file);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'AI Identification failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Perform general market analysis for an item image.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function marketAnalyze(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB
        ]);

        try {
            $file = $request->file('image');
            $result = $this->ai->marketAnalysis($file);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Market analysis failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Perform Facebook Marketplace specific analysis.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function facebookAnalyze(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB
        ]);

        try {
            $file = $request->file('image');
            $result = $this->ai->facebookAnalysis($file);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Facebook analysis failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Perform Etsy specific analysis.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function etsyAnalyze(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB
        ]);

        try {
            $file = $request->file('image');
            $result = $this->ai->etsyAnalysis($file);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Etsy analysis failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Perform a shotgun scan of an image containing multiple items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function shotgunScan(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB
        ]);

        try {
            $file = $request->file('image');
            $result = $this->ai->shotgunScan($file);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Shotgun scan failed: ' . $e->getMessage()], 500);
        }
    }
}
