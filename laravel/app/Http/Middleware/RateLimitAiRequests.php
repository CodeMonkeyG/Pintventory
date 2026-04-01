<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to protect expensive AI endpoints from abuse.
 * 
 * Implements a per-user rate limit for requests directed to AI analysis drivers.
 */
class RateLimitAiRequests
{
    /**
     * Rate limiter instance
     *
     * @var \Illuminate\Cache\RateLimiter
     */
    protected $limiter;

    /**
     * Create a new middleware instance
     *
     * @param  \Illuminate\Cache\RateLimiter  $limiter
     */
    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request for AI endpoints with rate limiting
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Rate limit: 10 requests per minute per user for AI endpoints
        $key = "ai-requests:{$user->id}";
        $maxRequests = 10;
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $maxRequests)) {
            $retryAfter = $this->limiter->availableIn($key);
            return response()->json(
                ['message' => "Too many AI requests. Retry after {$retryAfter} seconds."],
                429
            )->header('Retry-After', $retryAfter);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }
}
