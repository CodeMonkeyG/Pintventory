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
            // Pass the UploadedFile object directly to the AI driver.
            $file = $request->file('image');
            $result = $this->ai->identifyImage($file);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['message' => 'AI Identification failed: ' . $e->getMessage()], 500);
        }
    }
}
