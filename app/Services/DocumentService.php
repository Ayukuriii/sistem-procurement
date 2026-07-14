<?php

namespace App\Services;

use App\Constants\Documents;
use App\Constants\Statuses;
use App\Models\Document;
use App\Models\PurchaseOrder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Class DocumentService
 */
class DocumentService
{
    public function store(UploadedFile $file, ?array $metadata, string $poPublicId): Document
    {
        $purchaseOrder = PurchaseOrder::firstWhere('public_id', $poPublicId);

        if (! $purchaseOrder) {
            throw new \Exception('Purchase order not found');
        }

        $this->assertDraft($purchaseOrder);

        return DB::transaction(function () use ($file, $metadata, $purchaseOrder) {
            $existing = $purchaseOrder->document;
            $oldPath = $existing?->file_path;

            $fileName = Str::uuid7()->toString().'.pdf';
            $filePath = Documents::STORAGE_DIR.'/'.$purchaseOrder->public_id.'/'.$fileName;

            $stored = Storage::disk(Documents::STORAGE_DISK)->putFileAs(
                Documents::STORAGE_DIR.'/'.$purchaseOrder->public_id,
                $file,
                $fileName
            );

            if (! $stored) {
                throw new \Exception('Failed to store document file.');
            }

            $documentMetadata = array_merge($metadata ?? [], [
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => Documents::ALLOWED_MIME,
            ]);

            // file_url is computed at response time from file_path — never persist it
            unset($documentMetadata['file_url']);

            if ($existing) {
                $existing->update([
                    'file_path' => $filePath,
                    'metadata' => $documentMetadata,
                ]);

                $this->deleteFileFromDisk($oldPath);

                return $existing->fresh();
            }

            return $purchaseOrder->document()->create([
                'file_path' => $filePath,
                'metadata' => $documentMetadata,
            ]);
        });
    }

    /**
     * @return array<int, Document>
     */
    public function listByPurchaseOrder(string $poPublicId): array
    {
        $purchaseOrder = PurchaseOrder::firstWhere('public_id', $poPublicId);

        if (! $purchaseOrder) {
            throw new \Exception('Purchase order not found');
        }

        $document = $purchaseOrder->document;

        return $document ? [$document] : [];
    }

    public function show(string $documentPublicId): Document
    {
        $document = Document::firstWhere('public_id', $documentPublicId);

        if (! $document) {
            throw new \Exception('Document not found');
        }

        return $document;
    }

    private function assertDraft(PurchaseOrder $purchaseOrder): void
    {
        if ($purchaseOrder->status !== Statuses::PO_DRAFT) {
            throw new \Exception(
                'Cannot upload document. Purchase order is already '.$purchaseOrder->status
            );
        }
    }

    private function deleteFileFromDisk(?string $path): void
    {
        if ($path && Storage::disk(Documents::STORAGE_DISK)->exists($path)) {
            Storage::disk(Documents::STORAGE_DISK)->delete($path);
        }
    }
}
