<?php

namespace App\Services;

use App\Constants\Audits;
use App\Constants\Paginations;
use App\Constants\Roles;
use App\Models\Audit;
use App\Models\Document;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\User;
use App\Services\Audit\AuditDescriptionBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AuditService
{
    private const DEFAULT_PER_PAGE = Paginations::DEFAULT_PER_PAGE;

    public function __construct(
        protected AuditDescriptionBuilder $descriptionBuilder
    ) {}

    /**
     * @param  array{
     *     auditable_type?: ?string,
     *     auditable_id?: ?string,
     *     user_id?: ?string,
     *     event?: ?string,
     *     date_from?: ?string,
     *     date_to?: ?string,
     *     sort?: ?string
     * }  $filters
     */
    public function paginate(array $filters, int $perPage = self::DEFAULT_PER_PAGE): LengthAwarePaginator
    {
        $hasResourceScope = filled($filters['auditable_type'] ?? null)
            && filled($filters['auditable_id'] ?? null);

        if (! $hasResourceScope) {
            $this->assertAdministrator();
        }

        $query = Audit::query()->with([
            'user',
            'auditable',
        ]);

        if ($hasResourceScope) {
            $this->applyResourceScope(
                $query,
                (string) $filters['auditable_type'],
                (string) $filters['auditable_id']
            );
        } elseif (filled($filters['auditable_type'] ?? null)) {
            $class = Audits::typeToClass((string) $filters['auditable_type']);
            if (! $class) {
                throw new NotFoundHttpException('Jenis resource audit tidak dikenali.');
            }
            $query->where('auditable_type', $class);
        }

        if (filled($filters['user_id'] ?? null)) {
            $actor = User::query()->firstWhere('public_id', $filters['user_id']);
            if (! $actor) {
                throw new NotFoundHttpException('User tidak ditemukan.');
            }
            $query->where('user_id', $actor->id);
        }

        if (filled($filters['event'] ?? null)) {
            $query->where('event', $filters['event']);
        }

        if (filled($filters['date_from'] ?? null)) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (filled($filters['date_to'] ?? null)) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $this->applySort($query, $filters['sort'] ?? null);

        $paginator = $query->paginate($perPage);

        $paginator->getCollection()->transform(function (Audit $audit) {
            $audit->setAttribute('action', Audits::actionLabel($audit->event));
            $audit->setAttribute('description', $this->descriptionBuilder->build($audit));
            $audit->setAttribute(
                'auditable_type_slug',
                Audits::classToType($audit->auditable_type)
            );
            $audit->setAttribute(
                'auditable_public_id',
                $audit->auditable?->public_id
            );

            return $audit;
        });

        return $paginator;
    }

    private function assertAdministrator(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->hasRole(Roles::ROLE_ADMIN)) {
            throw new AccessDeniedHttpException(
                'Hanya administrator yang dapat melihat daftar audit global.'
            );
        }
    }

    private function applyResourceScope(Builder $query, string $type, string $publicId): void
    {
        $class = Audits::typeToClass($type);

        if (! $class) {
            throw new NotFoundHttpException('Jenis resource audit tidak dikenali.');
        }

        /** @var Model|null $resource */
        $resource = $class::query()
            ->when(
                method_exists($class, 'bootSoftDeletes') || in_array(SoftDeletes::class, class_uses_recursive($class), true),
                fn (Builder $q) => $q->withTrashed()
            )
            ->where('public_id', $publicId)
            ->first();

        if (! $resource) {
            throw new NotFoundHttpException('Resource yang diaudit tidak ditemukan.');
        }

        if ($type === Audits::TYPE_PURCHASE_ORDER && $resource instanceof PurchaseOrder) {
            $itemIds = PurchaseOrderItem::withTrashed()
                ->where('purchase_order_id', $resource->id)
                ->pluck('id')
                ->all();

            $documentIds = Document::withTrashed()
                ->where('documentable_type', PurchaseOrder::class)
                ->where('documentable_id', $resource->id)
                ->pluck('id')
                ->all();

            $query->where(function (Builder $q) use ($resource, $itemIds, $documentIds) {
                $q->where(function (Builder $inner) use ($resource) {
                    $inner->where('auditable_type', PurchaseOrder::class)
                        ->where('auditable_id', $resource->id);
                });

                if ($itemIds !== []) {
                    $q->orWhere(function (Builder $inner) use ($itemIds) {
                        $inner->where('auditable_type', PurchaseOrderItem::class)
                            ->whereIn('auditable_id', $itemIds);
                    });
                }

                if ($documentIds !== []) {
                    $q->orWhere(function (Builder $inner) use ($documentIds) {
                        $inner->where('auditable_type', Document::class)
                            ->whereIn('auditable_id', $documentIds);
                    });
                }
            });

            return;
        }

        $query->where('auditable_type', $class)
            ->where('auditable_id', $resource->id);
    }

    private function applySort(Builder $query, ?string $sort): void
    {
        $sort ??= '-created_at';
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        if ($column !== 'created_at') {
            $column = 'created_at';
            $direction = 'desc';
        }

        $query->orderBy($column, $direction);
    }
}
