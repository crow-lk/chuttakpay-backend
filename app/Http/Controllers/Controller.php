<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Resolve the authenticated user from Sanctum tokens without requiring middleware.
     */
    protected function resolveAuthenticatedUser(Request $request): ?User
    {
        return $request->user('sanctum') ?? $request->user();
    }
}
