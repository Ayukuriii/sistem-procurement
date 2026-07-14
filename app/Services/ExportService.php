<?php

namespace App\Services;

use App\Constants\Exports;
use App\Constants\Paginations;
use App\Jobs\ProcessExportJob;
use App\Models\ExportJob;
use App\Models\User;
use App\Services\ExportImport\ModuleHandlerRegistry;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ExportService
{
    private const DEFAULT_PER_PAGE = Paginations::DEFAULT_PER_PAGE;

    public function __construct(
        protected ModuleHandlerRegistry $registry
    ) {}

    /**
     * @param  array{module?: ?string}  $filters
     */
    public function paginateForUser(User $user, array $filters, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $query = ExportJob::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if (! empty($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        return $query->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function queue(User $user, string $module, array $filters = []): ExportJob
    {
        $this->registry->exportable($module);

        $job = ExportJob::create([
            'user_id' => $user->id,
            'module' => $module,
            'status' => Exports::STATUS_QUEUED,
            'filters' => $filters,
        ]);

        ProcessExportJob::dispatch($job->id);

        return $job->fresh();
    }

    public function findForUser(User $user, string $publicId): ExportJob
    {
        $job = ExportJob::query()
            ->where('public_id', $publicId)
            ->where('user_id', $user->id)
            ->first();

        if (! $job) {
            throw new NotFoundHttpException('Export job not found');
        }

        return $job;
    }
}
