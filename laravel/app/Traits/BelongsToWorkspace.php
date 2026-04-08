<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait BelongsToWorkspace
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToWorkspace()
    {
        static::creating(function ($model) {
            if (empty($model->workspace_id) && Auth::check()) {
                $model->workspace_id = Auth::user()->current_workspace_id;
            }
        });
    }
}
