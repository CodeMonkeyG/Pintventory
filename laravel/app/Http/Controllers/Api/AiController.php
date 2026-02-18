<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\AiProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AiController extends Controller
{
    protected $ai;

    public function __construct(AiProvider $ai)
    {
        $this->ai = $ai;
    }

    public function identify(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // 10MB
        ]);

        try {
            // Store the uploaded file locally on the public disk so it's accessible during development.
            $file = $request->file('image');
            $path = Storage::disk('public')->putFile('ai_uploads', $file);
            $publicUrl = Storage::disk('public')->url($path);
            Log::info('AI upload stored at: ' . $publicUrl);

            // Pass the public URL to the AI driver. In the driver we will override this URL
            // with a hardcoded remote image for local-development testing before calling OpenAI.
            $result = $this->ai->identifyImage($publicUrl);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'AI Identification failed: ' . $e->getMessage()], 500);
        }
    }
}
