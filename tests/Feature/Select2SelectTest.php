<?php

namespace Tests\Feature;

use App\Constants\Audits;
use App\Constants\Exports;
use App\Constants\Roles;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class Select2SelectTest extends TestCase
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

    public function test_unauthenticated_request_returns_401(): void
    {
        $this->getJson('/api/roles/select')->assertUnauthorized();
    }

    public function test_roles_select_returns_seeded_roles_with_public_id(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/roles/select');

        $response->assertOk()
            ->assertJsonStructure([
                'results' => [['id', 'text']],
                'pagination' => ['more'],
            ]);

        $results = collect($response->json('results'));
        $this->assertTrue($results->contains(fn ($r) => $r['text'] === Roles::ROLE_ADMIN));
        $this->assertTrue($results->contains(fn ($r) => $r['text'] === Roles::ROLE_STAFF));

        $adminRole = Role::where('name', Roles::ROLE_ADMIN)->first();
        $matched = $results->firstWhere('text', Roles::ROLE_ADMIN);
        $this->assertSame($adminRole->public_id, $matched['id']);
    }

    public function test_category_select_filters_by_q(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        Category::create(['name' => 'elektronik']);
        Category::create(['name' => 'furniture']);

        $response = $this->getJson('/api/categories/select?q=elek');

        $response->assertOk();
        $texts = collect($response->json('results'))->pluck('text')->all();
        $this->assertContains('elektronik', $texts);
        $this->assertNotContains('furniture', $texts);
    }

    public function test_supplier_select_excludes_inactive(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $active = Supplier::create([
            'name' => 'Active Supplier',
            'email' => 'active@example.com',
            'phone' => '0811111111',
            'address' => 'Jakarta',
            'is_active' => true,
        ]);
        Supplier::create([
            'name' => 'Inactive Supplier',
            'email' => 'inactive@example.com',
            'phone' => '0822222222',
            'address' => 'Bandung',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/suppliers/select');

        $response->assertOk();
        $ids = collect($response->json('results'))->pluck('id')->all();
        $this->assertContains($active->public_id, $ids);
        $this->assertCount(1, $ids);
    }

    public function test_product_select_text_contains_sku_and_name_and_excludes_inactive(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $category = Category::create(['name' => 'kabel']);
        $active = Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-100',
            'name' => 'HDMI Cable',
            'unit_price' => 50000,
            'is_active' => true,
        ]);
        Product::create([
            'category_id' => $category->id,
            'sku' => 'SKU-200',
            'name' => 'Old Cable',
            'unit_price' => 10000,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/products/select');

        $response->assertOk();
        $results = collect($response->json('results'));
        $this->assertCount(1, $results);

        $option = $results->first();
        $this->assertSame($active->public_id, $option['id']);
        $this->assertStringContainsString('SKU-100', $option['text']);
        $this->assertStringContainsString('HDMI Cable', $option['text']);
    }

    public function test_audit_types_and_events_select(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $types = $this->getJson('/api/audits/types/select');
        $types->assertOk();
        $typeIds = collect($types->json('results'))->pluck('id')->all();
        $this->assertContains(Audits::TYPE_PRODUCT, $typeIds);
        $this->assertContains(Audits::TYPE_PURCHASE_ORDER, $typeIds);

        $filtered = $this->getJson('/api/audits/types/select?q=purchase');
        $filtered->assertOk();
        $filteredIds = collect($filtered->json('results'))->pluck('id')->all();
        $this->assertContains(Audits::TYPE_PURCHASE_ORDER, $filteredIds);
        $this->assertNotContains(Audits::TYPE_USER, $filteredIds);

        $events = $this->getJson('/api/audits/events/select');
        $events->assertOk();
        $eventIds = collect($events->json('results'))->pluck('id')->all();
        $this->assertContains(Audits::EVENT_CREATED, $eventIds);
        $this->assertContains(Audits::EVENT_STATUS_CHANGED, $eventIds);

        $created = collect($events->json('results'))->firstWhere('id', Audits::EVENT_CREATED);
        $this->assertSame(Audits::actionLabel(Audits::EVENT_CREATED), $created['text']);
    }

    public function test_export_modules_select_requires_admin(): void
    {
        $staff = $this->makeUser(Roles::ROLE_STAFF);
        Sanctum::actingAs($staff);

        $this->getJson('/api/exports/modules/select')->assertForbidden();

        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/exports/modules/select');
        $response->assertOk();

        $ids = collect($response->json('results'))->pluck('id')->all();
        foreach (Exports::EXPORTABLE as $module) {
            $this->assertContains($module, $ids);
        }
    }

    public function test_import_modules_select_excludes_purchase_orders(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/imports/modules/select');
        $response->assertOk();

        $ids = collect($response->json('results'))->pluck('id')->all();
        foreach (Exports::IMPORTABLE as $module) {
            $this->assertContains($module, $ids);
        }
        $this->assertNotContains(Exports::MODULE_PURCHASE_ORDERS, $ids);
    }

    public function test_pagination_more_when_per_page_is_one(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        Category::create(['name' => 'alpha']);
        Category::create(['name' => 'beta']);

        $page1 = $this->getJson('/api/categories/select?per_page=1&page=1');
        $page1->assertOk()
            ->assertJsonPath('pagination.more', true);
        $this->assertCount(1, $page1->json('results'));

        $page2 = $this->getJson('/api/categories/select?per_page=1&page=2');
        $page2->assertOk()
            ->assertJsonPath('pagination.more', false);
        $this->assertCount(1, $page2->json('results'));
    }

    public function test_search_alias_works_like_q(): void
    {
        $admin = $this->makeUser(Roles::ROLE_ADMIN);
        Sanctum::actingAs($admin);

        Category::create(['name' => 'network']);
        Category::create(['name' => 'office']);

        $response = $this->getJson('/api/categories/select?search=net');
        $response->assertOk();
        $texts = collect($response->json('results'))->pluck('text')->all();
        $this->assertContains('network', $texts);
        $this->assertNotContains('office', $texts);
    }
}
