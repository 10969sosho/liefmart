<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IncreaseUploadLimits
{
    /**
     * Handle an incoming request by increasing upload limits for Excel imports.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Periksa apakah request memiliki file
        if ($request->hasFile('file')) {
            \Log::info('File terdeteksi dalam request: ' . $request->file('file')->getClientOriginalName());
        } else {
            $hasFiles = $request->allFiles();
            \Log::info('Tidak ada file terdeteksi dalam request. Files present: ' . json_encode(array_keys($hasFiles)));
        }
        
        // Log current PHP settings without modifying them to let hosting settings take precedence
        \Log::info('Current PHP settings:', [
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_vars' => ini_get('max_input_vars')
        ]);
        
        return $next($request);
    }
} 