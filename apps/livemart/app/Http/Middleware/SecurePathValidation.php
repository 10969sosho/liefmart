<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Shared\Helpers\SecurePathHelper;
use Illuminate\Support\Facades\Log;

class SecurePathValidation
{
    /**
     * Handle an incoming request.
     * Validates that all paths are secure and within tolerance
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Validate security on every request
            $securityStatus = SecurePathHelper::getSecurityStatus();
            
            if (!$securityStatus['overall_secure']) {
                Log::error('Security validation failed', $securityStatus);
                
                // Return error response instead of continuing
                return response()->json([
                    'error' => 'Security validation failed',
                    'message' => 'Path validation failed - exceeds security tolerance',
                    'details' => $securityStatus['validation']
                ], 403);
            }
            
            // Log security status periodically (1% chance)
            if (rand(1, 100) <= 1) {
                Log::info('Security validation passed', [
                    'base_path' => $securityStatus['base_path'],
                    'max_parent_levels' => $securityStatus['max_parent_levels']
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Security validation error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Security validation error',
                'message' => 'Failed to validate path security',
                'details' => $e->getMessage()
            ], 500);
        }
        
        return $next($request);
    }
}
