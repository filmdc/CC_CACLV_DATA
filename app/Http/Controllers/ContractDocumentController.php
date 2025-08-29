<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;

class ContractDocumentController extends Controller
{
    public function download($contractId, $mediaId)
    {
        if (!auth()->user()->can('contracts.view')) {
            abort(403, 'Unauthorized');
        }
        
        try {
            $contract = Contract::findOrFail($contractId);
            $media = $contract->getMedia('documents')->where('id', $mediaId)->first();
            
            if (!$media) {
                abort(404, 'Document not found');
            }
            
            // Find the actual file location
            $filePath = $this->findMediaFile($media, $contractId);
            
            if (!$filePath) {
                abort(404, 'Document file not found in storage');
            }
            
            return response()->download($filePath, $media->name);
            
        } catch (\Exception $e) {
            abort(500, 'Cannot download document: ' . $e->getMessage());
        }
    }
    
    public function preview($contractId, $mediaId)
    {
        if (!auth()->user()->can('contracts.view')) {
            abort(403, 'Unauthorized');
        }
        
        try {
            $contract = Contract::findOrFail($contractId);
            $media = $contract->getMedia('documents')->where('id', $mediaId)->first();
            
            if (!$media) {
                abort(404, 'Document not found');
            }
            
            // Find the actual file location
            $path = $this->findMediaFile($media, $contractId);
            
            if (!$path) {
                abort(404, 'Document file not found in storage');
            }
            
            $mimeType = $media->mime_type;
            
            // For PDFs and images, return inline response
            if (in_array($mimeType, ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'])) {
                return response()->file($path, [
                    'Content-Type' => $mimeType,
                    'Content-Disposition' => 'inline; filename="' . $media->name . '"'
                ]);
            }
            
            // For other types, download
            return response()->download($path, $media->name);
            
        } catch (\Exception $e) {
            abort(500, 'Cannot preview document: ' . $e->getMessage());
        }
    }
    
    /**
     * Find the actual file location for a media record
     */
    private function findMediaFile($media, $contractId)
    {
        $fileName = $media->file_name;
        
        // Try locations in order of preference:
        
        // 1. Expected media library path (media ID directory)
        $expectedPath = $media->getPath();
        if (file_exists($expectedPath)) {
            return $expectedPath;
        }
        
        // 2. Contract directory
        $contractPath = storage_path("app/public/{$contractId}/{$fileName}");
        if (file_exists($contractPath)) {
            return $contractPath;
        }
        
        // 3. Search all storage directories for this file
        $storageBase = storage_path("app/public");
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($storageBase, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $fileName) {
                return $file->getPathname();
            }
        }
        
        return null;
    }
}
