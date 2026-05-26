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
        // NOTE: max_input_vars, post_max_size, dan upload_max_filesize 
        // TIDAK BISA diubah via ini_set() - harus diubah di php.ini!
        // Setting di bawah hanya untuk yang bisa diubah di runtime.
        
        // Increase limits yang bisa diubah via ini_set()
        @ini_set('max_execution_time', '300');    // ✅ Bisa diubah
        @ini_set('memory_limit', '512M');         // ✅ Bisa diubah
        
        // Setting di bawah TIDAK EFEKTIF (hanya untuk dokumentasi)
        // Harus diubah di php.ini XAMPP!
        // @ini_set('max_input_vars', '5000');       // ❌ Tidak bisa diubah
        // @ini_set('post_max_size', '100M');        // ❌ Tidak bisa diubah
        // @ini_set('upload_max_filesize', '100M');  // ❌ Tidak bisa diubah
        
        // Log settings untuk debugging (hanya log, tidak ubah setting)
        \Log::info('PHP settings check:', [
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_vars' => ini_get('max_input_vars'),
            'post_max_size' => ini_get('post_max_size'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
        ]);
        
        return $next($request);
    }
} 