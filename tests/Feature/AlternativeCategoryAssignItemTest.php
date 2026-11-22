<?php

namespace Tests\Feature;

use App\Http\Controllers\AlternativeCategoryController;
use App\Http\Requests\AssignItemToAlternativeCategoryRequest;
use App\Models\AlternativeCategory;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\CreatesRestErpTables;
use Tests\TestCase;

class AlternativeCategoryAssignItemTest extends TestCase
{
    use CreatesRestErpTables;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createRestErpTables();
        Schema::create('erp_product_items', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
        });
        Schema::create('alternative_categories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('alternative_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('alternative_category_id');
            $table->unsignedBigInteger('erp_product_item_id');
            $table->unsignedBigInteger('user_id');
            $table->string('item_no');
            $table->decimal('packing_qty', 10, 2);
            $table->timestamps();
        });
        DB::table('erp_product_items')->insert(['id' => 1, 'reference' => 'PART-1']);
        DB::table('alternative_categories')->insert([['id' => 1], ['id' => 2]]);
    }

    public function test_another_users_assignment_is_rejected_even_in_another_category(): void
    {
        $this->insertAssignment(43);
        DB::table('users')->where('id', 43)->update(['deleted_at' => now()]);

        $response = $this->assign(2);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertStringContainsString('Retry actor', $response->getData()->message);
        $this->assertDatabaseCount('alternative_items', 1);
    }

    public function test_same_user_can_assign_to_another_category_but_not_twice_to_the_same_category(): void
    {
        $this->insertAssignment(42);
        $response = $this->assign(1);
        $this->assertSame(422, $response->getStatusCode());
        $this->assign(2);
        $this->assertDatabaseCount('alternative_items', 2);
    }

    private function insertAssignment(int $userId): void
    {
        DB::table('alternative_items')->insert(['alternative_category_id' => 1, 'erp_product_item_id' => 1, 'user_id' => $userId, 'item_no' => 'PART-1', 'packing_qty' => 1]);
    }

    private function assign(int $categoryId): mixed
    {
        $request = \Mockery::mock(AssignItemToAlternativeCategoryRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['erp_product_item_id' => 1, 'user_id' => 42, 'item_no' => 'PART-1', 'packing_qty' => 1]);
        return app(AlternativeCategoryController::class)->assignItem(AlternativeCategory::findOrFail($categoryId), $request);
    }
}
