<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface AiProvider
{
    public function identifyImage(UploadedFile $image): array;
}
