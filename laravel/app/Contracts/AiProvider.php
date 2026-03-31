<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface AiProvider
{
    public function identifyImage(UploadedFile|string $image): array;

    public function marketAnalysis(UploadedFile|string $image): array;

    public function facebookAnalysis(UploadedFile|string $image): array;

    public function etsyAnalysis(UploadedFile|string $image): array;
}
