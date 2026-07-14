<?php

namespace Tests\Feature;

use App\Constants\Exports;
use App\Constants\Roles;
use App\Constants\Statuses;
use App\Exports\ArraySheetExport;
use App\Mail\ExportReadyMail;
use App\Models\Category;
use App\Models\Document;
use App\Models\ExportJob;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ExportImportTest extends TestCase
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

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rows
     */
    private function makeXlsxUpload(array $headers, array $rows, string $filename = 'import.xlsx'): UploadedFile
    {
        $path = storage_path('framework/testing/'.$filename);
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        Excel::store(new ArraySheetExport($headers, $rows), 'testing/'.$filename, 'local');

        $fullPath = Storage::disk('local')->path('testing/'.$filename);

        return new UploadedFile(
            $fullPath,
            $filename,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    public function test_staff_cannot_access_export_import_or_template(): void
    {
        $staff = $this->makeUser(Roles::ROLE_STAFF);
        Sanctum::actingAs($staff);

        $this->getJson('/api/exports')->assertForbidden();
        $this->postJson('/api/exports', ['module' => 'products'])->assertForbidden();
        $this->getJson('/api/imports/template?module=products')->assertForbidden();
        $this->post('/api/imports', ['module' => 'products'])->assertForbidden();
    }

    public function test_admin_can_queue_export_and_receives_mail_when_completed(): void
    {
        Mail::fake();
        Storage::fake('public');

        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        Category::create(['name' => 'elektronik']);
        Product::create([
            'category_id' => Category::first()->id,
            'sku' => 'SKU-EXP-1',
            'name' => 'Mouse',
            'unit_price' => 50000,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/exports', [
            'module' => Exports::MODULE_PRODUCTS,
            'filters' => ['is_active' => true],
        ]);

        $response->assertAccepted()
            ->assertJsonPath('data.module', Exports::MODULE_PRODUCTS)
            ->assertJsonPath('data.status', Exports::STATUS_COMPLETED);

        $job = ExportJob::first();
        $this->assertNotNull($job);
        $this->assertSame(Exports::STATUS_COMPLETED, $job->status);
        $this->assertNotNull($job->download_url);
        $this->assertNotNull($job->path);
        Storage::disk('public')->assertExists($job->path);

        Mail::assertSent(ExportReadyMail::class, function (ExportReadyMail $mail) use ($admin) {
            return $mail->hasTo($admin->email);
        });

        $this->getJson('/api/exports/'.$job->public_id)
            ->assertOk()
            ->assertJsonPath('data.export_job_id', $job->public_id)
            ->assertJsonPath('data.status', Exports::STATUS_COMPLETED);
    }

    public function test_import_products_partial_success_with_duplicate_sku(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $category = Category::create(['name' => 'elektronik']);

        Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-DUP',
            'name' => 'Existing',
            'unit_price' => 1000,
            'is_active' => true,
        ]);

        $file = $this->makeXlsxUpload(
            ['category_id', 'sku', 'name', 'unit_price', 'is_active'],
            [
                [$category->public_id, 'SKU-NEW', 'New Product', 2000, '1'],
                [$category->public_id, 'SKU-DUP', 'Dup Product', 3000, '1'],
            ],
            'products-import.xlsx'
        );

        $response = $this->post('/api/imports', [
            'module' => Exports::MODULE_PRODUCTS,
            'file' => $file,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.module', Exports::MODULE_PRODUCTS)
            ->assertJsonPath('data.imported_count', 1)
            ->assertJsonPath('data.failed_count', 1);

        $this->assertDatabaseHas('products', ['sku' => 'SKU-NEW']);
        $this->assertSame(1, Product::where('sku', 'SKU-DUP')->count());
    }

    public function test_import_users_uses_default_password_and_role_column(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $file = $this->makeXlsxUpload(
            ['name', 'email', 'role', 'is_active'],
            [
                ['Staff Imported', 'staff.import@example.com', Roles::ROLE_STAFF, '1'],
            ],
            'users-import.xlsx'
        );

        $this->post('/api/imports', [
            'module' => Exports::MODULE_USERS,
            'file' => $file,
        ])->assertOk()
            ->assertJsonPath('data.imported_count', 1)
            ->assertJsonPath('data.failed_count', 0);

        $user = User::where('email', 'staff.import@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole(Roles::ROLE_STAFF));
        $this->assertTrue(password_verify(Exports::DEFAULT_USER_PASSWORD, $user->password));
    }

    public function test_template_download_returns_xlsx(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $response = $this->get('/api/imports/template?module=suppliers');

        $response->assertOk();
        $this->assertStringContainsString(
            'spreadsheetml',
            (string) $response->headers->get('content-type')
        );
    }

    public function test_invalid_module_and_bad_headers_return_422(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $this->postJson('/api/exports', ['module' => 'unknown'])
            ->assertStatus(422);

        $this->getJson('/api/imports/template?module=purchase_orders')
            ->assertStatus(422);

        $file = $this->makeXlsxUpload(
            ['wrong', 'headers'],
            [['a', 'b']],
            'bad-headers.xlsx'
        );

        $this->post('/api/imports', [
            'module' => Exports::MODULE_PRODUCT_CATEGORIES,
            'file' => $file,
        ])->assertStatus(422);
    }

    public function test_purchase_order_export_is_flat_with_document_url(): void
    {
        Mail::fake();
        Storage::fake('public');

        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $supplier = Supplier::create([
            'name' => 'Supplier A',
            'email' => 'sup@example.com',
            'phone' => '08111',
            'address' => 'Addr',
            'is_active' => true,
        ]);

        $category = Category::create(['name' => 'cat']);
        $product = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-PO-1',
            'name' => 'Item A',
            'unit_price' => 10000,
            'is_active' => true,
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-2026-001',
            'supplier_id' => $supplier->id,
            'creator_id' => $admin->id,
            'order_date' => now(),
            'status' => Statuses::PO_DRAFT,
            'is_urgent' => false,
        ]);

        PurchaseOrderItem::create([
            'purchase_order_id' => $po->id,
            'product_id' => $product->id,
            'product_name_snapshot' => $product->name,
            'unit_price_snapshot' => 10000,
            'quantity' => 2,
            'subtotal' => 20000,
        ]);

        $docPath = 'purchase-orders/'.$po->public_id.'/test.pdf';
        Storage::disk('public')->put($docPath, 'fake-pdf');

        Document::create([
            'documentable_type' => PurchaseOrder::class,
            'documentable_id' => $po->id,
            'file_path' => $docPath,
            'metadata' => ['original_name' => 'test.pdf'],
        ]);

        $this->postJson('/api/exports', [
            'module' => Exports::MODULE_PURCHASE_ORDERS,
        ])->assertAccepted()
            ->assertJsonPath('data.status', Exports::STATUS_COMPLETED);

        $job = ExportJob::where('module', Exports::MODULE_PURCHASE_ORDERS)->first();
        $this->assertNotNull($job);
        Storage::disk('public')->assertExists($job->path);

        $rows = Excel::toArray(new \stdClass, Storage::disk('public')->path($job->path))[0];
        $headers = $rows[0];
        $this->assertContains('document_url', $headers);
        $this->assertContains('po_number', $headers);
        $this->assertContains('product_sku', $headers);

        $dataRow = $rows[1];
        $urlIndex = array_search('document_url', $headers, true);
        $poIndex = array_search('po_number', $headers, true);
        $skuIndex = array_search('product_sku', $headers, true);

        $this->assertSame('PO-2026-001', $dataRow[$poIndex]);
        $this->assertSame('SKU-PO-1', $dataRow[$skuIndex]);
        $this->assertNotEmpty($dataRow[$urlIndex]);
        $this->assertStringContainsString('purchase-orders', (string) $dataRow[$urlIndex]);
    }
}
