<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function store(Request $request)
    {
        return $request;
    }
    public function video_upload(Request $request)
    {
        try {
            // Validation
            $request->validate([
                'video' => 'required|file',
                'chunkIndex' => 'required|integer',
                'totalChunks' => 'required|integer',
            ]);
    
            $file = $request->file('video');
            $originalExtension = $file->getClientOriginalExtension();
    
            $chunkIndex = $request->input('chunkIndex');
            $totalChunks = $request->input('totalChunks');
    
            // Get the random title generated in JavaScript
            $videoTitle = $request->input('random_title');
    
            $filename = 'your_prefix_' . $videoTitle . '.mp4';
            $chunkFilename = 'chunk_' . $chunkIndex . '_' . $filename;
    
            // Save the chunk
            Storage::disk('local')->put('temp/' . $chunkFilename, file_get_contents($file));
    
            // If this is the last chunk, combine all the chunks into a single file
            if ($chunkIndex == $totalChunks - 1) {
                $this->combineChunks($filename, $totalChunks);
            }
    
            // Return a success response with the file path
            return response()->json(['status' => 'success', 'path' => $filename]);
        } catch (\Exception $e) {
            // Log the error
            \Log::error("Error in video_upload: " . $e->getMessage());
    
            // Return an error response
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
    // ...
    
    // In your combineChunks method, instead of throwing an exception, you can return a response like this:
    private function combineChunks($filename, $totalChunks)
    {
        try {
            $directory = storage_path('app/public/videos/');
    
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }
    
            $filePath = $directory . '/' . $filename;
    
            $handle = fopen($filePath, 'wb');
    
            if (!$handle) {
                return response()->json(['status' => 'error', 'message' => "Could not open file for writing: " . $filename], 500);
            }
    
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkFilename = 'chunk_' . $i . '_' . $filename;
                $chunkPath = storage_path('app/temp/' . $chunkFilename);
    
                if (!file_exists($chunkPath)) {
                    return response()->json(['status' => 'error', 'message' => "Chunk not found: " . $chunkFilename], 500);
                }
    
                fwrite($handle, file_get_contents($chunkPath));
                unlink($chunkPath); // Delete the chunk
            }
    
            fclose($handle);
    
            // Return a success response here if the operation is successful
            return response()->json(['status' => 'success', 'message' => 'File combined successfully']);
        } catch (\Exception $e) {
            // Log the error
            \Log::error("Error in combineChunks: " . $e->getMessage());
    
            // Return an error response
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
    
}
