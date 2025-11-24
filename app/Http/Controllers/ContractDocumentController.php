<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Support\Facades\Auth;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ContractDocumentController extends Controller
{
    public function download(int $contractId, int $mediaId): BinaryFileResponse
    {
        [$media, $path] = $this->resolveContractMedia($contractId, $mediaId);

        return response()->download($path, $media->name);
    }
    
    public function preview(int $contractId, int $mediaId): BinaryFileResponse
    {
        [$media, $path] = $this->resolveContractMedia($contractId, $mediaId);
        $mimeType = $media->mime_type;
        
        if (in_array($mimeType, ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'])) {
            return response()->file($path, [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'inline; filename="' . $media->name . '"',
            ]);
        }

        return response()->download($path, $media->name);
    }
    
    /**
     * @return array{0: Media, 1: string}
     */
    private function resolveContractMedia(int $contractId, int $mediaId): array
    {
        $this->authorizeContractAccess();

        $contract = Contract::findOrFail($contractId);
        $media = $contract->getMedia('documents')->firstWhere('id', $mediaId);

        abort_if(!$media, 404, 'Document not found');

        $path = $this->findMediaFile($media, $contractId);
        abort_if(!$path, 404, 'Document file not found in storage');

        return [$media, $path];
    }

    private function authorizeContractAccess(): void
    {
        abort_unless(Auth::user()?->can('contracts.view'), 403, 'Unauthorized');
    }
    
    /**
     * Locate the actual file for a media record across expected storage paths.
     */
    private function findMediaFile(Media $media, int $contractId): ?string
    {
        $fileName = $media->file_name;

        $expectedPath = $media->getPath();
        if (is_string($expectedPath) && file_exists($expectedPath)) {
            return $expectedPath;
        }

        $contractPath = storage_path("app/public/{$contractId}/{$fileName}");
        if (file_exists($contractPath)) {
            return $contractPath;
        }

        $storageBase = storage_path('app/public');
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($storageBase, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $fileName) {
                return $file->getPathname();
            }
        }

        return null;
    }
}
