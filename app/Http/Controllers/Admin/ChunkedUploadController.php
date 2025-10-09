<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChunkedUploadController extends Controller
{
    public function uploadChunk(Request $request)
    {
        // Check if user is superadmin (role_id = 1)
        if (auth()->user()->role_id != 1) {
            abort(403, 'Unauthorized access. Only superadmin can upload files.');
        }

        $request->validate([
            'chunk' => 'required|file|max:2048', // 2MB per chunk
            'chunk_number' => 'required|integer|min:0',
            'total_chunks' => 'required|integer|min:1',
            'file_name' => 'required|string|max:255',
            'file_size' => 'required|integer|min:1',
        ]);

        try {
            $chunk = $request->file('chunk');
            $chunkNumber = $request->chunk_number;
            $totalChunks = $request->total_chunks;
            $fileName = $request->file_name;
            $fileSize = $request->file_size;
            
            // Generate unique file identifier
            $fileId = md5($fileName . $fileSize . time());
            $chunkDir = storage_path('app/chunks/' . $fileId);
            
            // Create chunks directory
            if (!file_exists($chunkDir)) {
                mkdir($chunkDir, 0755, true);
            }
            
            // Save chunk
            $chunkPath = $chunkDir . '/chunk_' . $chunkNumber;
            $chunk->move($chunkDir, 'chunk_' . $chunkNumber);
            
            Log::info("Chunk uploaded: $chunkNumber/$totalChunks for file: $fileName");
            
            return response()->json([
                'success' => true,
                'chunk_number' => $chunkNumber,
                'total_chunks' => $totalChunks,
                'file_id' => $fileId
            ]);
            
        } catch (\Exception $e) {
            Log::error('Chunk upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function mergeChunks(Request $request)
    {
        // Check if user is superadmin (role_id = 1)
        if (auth()->user()->role_id != 1) {
            abort(403, 'Unauthorized access. Only superadmin can merge files.');
        }

        $request->validate([
            'file_id' => 'required|string',
            'file_name' => 'required|string|max:255',
            'total_chunks' => 'required|integer|min:1',
        ]);

        try {
            $fileId = $request->file_id;
            $fileName = $request->file_name;
            $totalChunks = $request->total_chunks;
            
            $chunkDir = storage_path('app/chunks/' . $fileId);
            $finalPath = storage_path('app/temp/' . $fileName);
            
            // Ensure temp directory exists
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }
            
            // Merge chunks
            $finalFile = fopen($finalPath, 'wb');
            if (!$finalFile) {
                throw new \Exception('Cannot create final file');
            }
            
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $chunkDir . '/chunk_' . $i;
                if (!file_exists($chunkPath)) {
                    throw new \Exception("Chunk $i not found");
                }
                
                $chunkFile = fopen($chunkPath, 'rb');
                if (!$chunkFile) {
                    throw new \Exception("Cannot read chunk $i");
                }
                
                while (!feof($chunkFile)) {
                    fwrite($finalFile, fread($chunkFile, 8192));
                }
                fclose($chunkFile);
            }
            fclose($finalFile);
            
            // Clean up chunks
            $this->cleanupChunks($chunkDir);
            
            Log::info("File merged successfully: $fileName");
            
            return response()->json([
                'success' => true,
                'file_path' => $finalPath,
                'file_name' => $fileName
            ]);
            
        } catch (\Exception $e) {
            Log::error('Chunk merge failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    private function cleanupChunks($chunkDir)
    {
        if (is_dir($chunkDir)) {
            $files = glob($chunkDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($chunkDir);
        }
    }
}
