<?php

namespace Tests\Feature;

use App\Constants\Audits;
use App\Constants\Roles;
use App\Constants\Statuses;
use App\Models\Audit;
use App\Models\Category;
use App\Models\Document;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProductService;
use App\Services\PurchaseOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private function seedRoles(): void
    {
        foreach ([Roles::ROLE_ADMIN, Roles::ROLE_STAFF] as $role) {
            Role::firstOrCreate(
                ['name' => $role, 'guard_name' => 'web'],
                ['public_id' => (string) Str::uuid7()]
            );
        }
    }

    private function makeUser(string $role): User
    {
        $this->seedRoles();

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function makeCategory(): Category
    {
        return Category::create(['name' => 'Elektronik']);
    }

    private function makeProduct(?Category $category = null): Product
    {
        $category ??= $this->makeCategory();

        return Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-001',
            'name' => 'Kabel HDMI',
            'unit_price' => 10000,
            'is_active' => true,
        ]);
    }

    public function test_creating_product_writes_created_audit_with_indonesian_description(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $category = $this->makeCategory();

        $product = app(ProductService::class)->store([
            'category_id' => $category->public_id,
            'sku' => 'SKU-100',
            'name' => 'Mouse Wireless',
            'unit_price' => 50000,
            'is_active' => true,
        ]);

        $audit = Audit::query()
            ->where('auditable_type', Product::class)
            ->where('auditable_id', $product->id)
            ->where('event', Audits::EVENT_CREATED)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame($admin->id, $audit->user_id);
        $this->assertNotEmpty($audit->public_id);
        $this->assertArrayNotHasKey('password', $audit->new_values ?? []);

        $response = $this->getJson('/api/audits?auditable_type=product&auditable_id='.$product->public_id);

        $response->assertOk()
            ->assertJsonPath('data.0.event', Audits::EVENT_CREATED)
            ->assertJsonPath('data.0.action', 'Dibuat');

        $this->assertStringContainsString('Membuat produk', $response->json('data.0.description'));
        $this->assertStringContainsString('SKU-100', $response->json('data.0.description'));
    }

    public function test_updating_product_description_includes_all_field_diffs(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $product = $this->makeProduct();

        $product->update([
            'name' => 'Kabel HDMI 2.0',
            'unit_price' => 12000,
        ]);

        $audit = Audit::query()
            ->where('auditable_type', Product::class)
            ->where('auditable_id', $product->id)
            ->where('event', Audits::EVENT_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);

        $response = $this->getJson('/api/audits?auditable_type=product&auditable_id='.$product->public_id);

        $description = collect($response->json('data'))
            ->firstWhere('event', Audits::EVENT_UPDATED)['description'] ?? '';

        $this->assertStringContainsString('harga satuan dari 10000 menjadi 12000', $description);
        $this->assertStringContainsString('nama dari Kabel HDMI menjadi Kabel HDMI 2.0', $description);
    }

    public function test_purchase_order_status_change_creates_status_changed_event(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $supplier = Supplier::create([
            'name' => 'PT Supplier',
            'email' => 'supplier@example.com',
            'phone' => '081234',
            'address' => 'Jakarta',
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-0001',
            'supplier_id' => $supplier->id,
            'creator_id' => $admin->id,
            'order_date' => now(),
            'status' => Statuses::PO_SUBMITTED,
            'is_urgent' => false,
        ]);

        app(PurchaseOrderService::class)->changeStatus([], $po->public_id, Statuses::PO_APPROVED);

        $audit = Audit::query()
            ->where('auditable_type', PurchaseOrder::class)
            ->where('auditable_id', $po->id)
            ->where('event', Audits::EVENT_STATUS_CHANGED)
            ->first();

        $this->assertNotNull($audit);
        $this->assertSame(Statuses::PO_SUBMITTED, $audit->old_values['status']);
        $this->assertSame(Statuses::PO_APPROVED, $audit->new_values['status']);

        $response = $this->getJson(
            '/api/audits?auditable_type=purchase_order&auditable_id='.$po->public_id.'&event=status_changed'
        );

        $response->assertOk();
        $this->assertStringContainsString('Mengubah status purchase order', $response->json('data.0.description'));
        $this->assertStringContainsString('submitted', $response->json('data.0.description'));
        $this->assertStringContainsString('approved', $response->json('data.0.description'));
    }

    public function test_staff_cannot_access_global_audit_list(): void
    {
        $staff = $this->makeUser(Roles::ROLE_STAFF);
        Sanctum::actingAs($staff);

        $this->getJson('/api/audits')
            ->assertForbidden();
    }

    public function test_admin_can_access_global_audit_list(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $this->makeProduct();

        $this->getJson('/api/audits')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    ['public_id', 'event', 'action', 'description', 'auditable_type', 'created_at'],
                ],
                'meta' => ['current_page', 'per_page', 'total', 'last_page'],
            ]);
    }

    public function test_staff_can_view_purchase_order_merged_timeline(): void
    {
        $staff = $this->makeUser(Roles::ROLE_STAFF);
        Sanctum::actingAs($staff);

        $supplier = Supplier::create([
            'name' => 'PT Supplier',
            'email' => 'supplier@example.com',
            'phone' => '081234',
            'address' => 'Jakarta',
            'is_active' => true,
        ]);

        $product = $this->makeProduct();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-0099',
            'supplier_id' => $supplier->id,
            'creator_id' => $staff->id,
            'order_date' => now(),
            'status' => Statuses::PO_DRAFT,
            'is_urgent' => false,
        ]);

        $item = PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'unit_price_snapshot' => $product->unit_price,
            'quantity' => 2,
            'subtotal' => $product->unit_price * 2,
        ]);

        $document = Document::create([
            'documentable_type' => PurchaseOrder::class,
            'documentable_id' => $po->id,
            'file_path' => 'documents/'.$po->public_id.'/file.pdf',
            'metadata' => [
                'file_name' => 'po-file.pdf',
                'mime_type' => 'application/pdf',
            ],
        ]);

        $response = $this->getJson(
            '/api/audits?auditable_type=purchase_order&auditable_id='.$po->public_id
        );

        $response->assertOk();

        $types = collect($response->json('data'))->pluck('auditable_type')->unique()->sort()->values()->all();

        $this->assertContains(Audits::TYPE_PURCHASE_ORDER, $types);
        $this->assertContains(Audits::TYPE_PURCHASE_ORDER_ITEM, $types);
        $this->assertContains(Audits::TYPE_DOCUMENT, $types);

        $this->assertTrue(
            Audit::query()->where('auditable_type', PurchaseOrderItem::class)->where('auditable_id', $item->id)->exists()
        );
        $this->assertTrue(
            Audit::query()->where('auditable_type', Document::class)->where('auditable_id', $document->id)->exists()
        );
    }

    public function test_user_password_is_not_stored_in_audit_values(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $target = User::factory()->create([
            'name' => 'Old Name',
            'password' => 'secret-password',
        ]);

        $target->update([
            'name' => 'New Name',
            'password' => 'new-secret-password',
        ]);

        $audit = Audit::query()
            ->where('auditable_type', User::class)
            ->where('auditable_id', $target->id)
            ->where('event', Audits::EVENT_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertArrayNotHasKey('password', $audit->old_values ?? []);
        $this->assertArrayNotHasKey('password', $audit->new_values ?? []);
        $this->assertArrayHasKey('name', $audit->new_values ?? []);
    }
}
