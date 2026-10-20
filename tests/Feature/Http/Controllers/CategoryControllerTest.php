<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    // Index

    public function test_guest_is_redirected_to_login_from_the_list(): void
    {
        $this->get(route('categories.index'))->assertRedirect(route('login'));
    }

    public function test_lists_default_categories_then_the_users_own_under_each_type(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'مواصلات']);
        $installments = Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);
        Category::factory()->income()->create(['name' => 'راتب']);
        $shopRent = Category::factory()->income()->ownedBy($user)->create(['name' => 'إيجار محل']);

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('categories/index')
                ->has('categories.expense', 2)
                ->where('categories.expense.0.name', 'مواصلات')
                ->where('categories.expense.0.is_default', true)
                ->where('categories.expense.1.id', $installments->id)
                ->where('categories.expense.1.is_default', false)
                ->has('categories.income', 2)
                ->where('categories.income.0.name', 'راتب')
                ->where('categories.income.0.is_default', true)
                ->where('categories.income.1.id', $shopRent->id)
                ->where('categories.income.1.is_default', false));
    }

    public function test_lists_the_catch_all_default_after_the_other_defaults(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'أخرى']);
        Category::factory()->create(['name' => 'مواصلات']);
        Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertInertia(fn (Assert $page) => $page->where(
                'categories.expense',
                fn ($categories) => collect($categories)->pluck('name')->all() === ['مواصلات', 'أخرى', 'أقساط'],
            ));
    }

    public function test_does_not_list_another_users_categories(): void
    {
        Category::factory()->ownedBy(User::factory()->create())->create(['name' => 'تصنيف خاص']);

        $this->actingAs(User::factory()->create())
            ->get(route('categories.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->missing('categories.expense'));
    }

    public function test_never_sends_the_page_a_raw_script_tag_from_a_category_name(): void
    {
        $user = User::factory()->create();
        Category::factory()->ownedBy($user)->create(['name' => "<script>alert('xss')</script>"]);

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertDontSee('<script>alert', escape: false);
    }

    // Store

    public function test_guest_cannot_add_a_category(): void
    {
        $this->post(route('categories.store'), ['name' => 'أقساط', 'type' => 'expense'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_valid_category_is_added_for_the_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'أقساط', 'type' => 'expense'])
            ->assertRedirect(route('categories.index'))
            ->assertInertiaFlash('status', 'تمت إضافة التصنيف.');

        $category = $user->categories()->sole();
        $this->assertSame('أقساط', $category->name);
        $this->assertSame(TransactionType::Expense, $category->type);
    }

    public function test_extra_fields_cannot_make_a_shared_default_or_assign_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)->post(route('categories.store'), [
            'name' => 'أقساط',
            'type' => 'expense',
            'user_id' => $otherUser->id,
        ]);

        $this->assertSame($user->id, Category::query()->sole()->user_id);
    }

    public function test_empty_form_is_rejected_with_a_message_for_each_field(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('categories.store'), [])
            ->assertSessionHasErrors([
                'name' => 'اكتب اسم التصنيف.',
                'type' => 'اختر نوع التصنيف.',
            ]);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_debt_is_not_a_category_type(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('categories.store'), ['name' => 'سلف', 'type' => 'debt'])
            ->assertSessionHasErrors(['type' => 'اختر نوع التصنيف.']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_rejects_a_name_that_repeats_a_default_category_of_the_same_type(): void
    {
        Category::factory()->create(['name' => 'أكل']);

        $this->actingAs(User::factory()->create())
            ->post(route('categories.store'), ['name' => 'أكل', 'type' => 'expense'])
            ->assertSessionHasErrors(['name' => 'هذا التصنيف موجود من قبل.']);

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_rejects_a_name_that_repeats_one_of_the_users_own_categories(): void
    {
        $user = User::factory()->create();
        Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'أقساط', 'type' => 'expense'])
            ->assertSessionHasErrors(['name' => 'هذا التصنيف موجود من قبل.']);

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_allows_the_same_name_under_the_other_type(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'أخرى']);

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'أخرى', 'type' => 'income'])
            ->assertSessionHasNoErrors();

        $this->assertSame(TransactionType::Income, $user->categories()->sole()->type);
    }

    public function test_allows_a_name_another_user_already_uses(): void
    {
        $user = User::factory()->create();
        Category::factory()->ownedBy(User::factory()->create())->create(['name' => 'أقساط']);

        $this->actingAs($user)
            ->post(route('categories.store'), ['name' => 'أقساط', 'type' => 'expense'])
            ->assertSessionHasNoErrors();

        $this->assertSame('أقساط', $user->categories()->sole()->name);
    }

    // Edit

    public function test_owner_sees_the_edit_form_with_the_current_name(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);

        $this->actingAs($user)
            ->get(route('categories.edit', $category))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('categories/edit')
                ->where('category.name', 'أقساط')
                ->where('typeLabel', 'مصروف'));
    }

    public function test_another_user_gets_not_found_from_the_edit_form(): void
    {
        $category = Category::factory()->ownedBy(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())
            ->get(route('categories.edit', $category))
            ->assertNotFound();
    }

    public function test_edit_form_is_forbidden_for_a_default_category(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('categories.edit', Category::factory()->create()))
            ->assertForbidden();
    }

    // Update

    public function test_owner_can_rename_a_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);

        $this->actingAs($user)
            ->put(route('categories.update', $category), ['name' => 'أقساط السيارة'])
            ->assertRedirect(route('categories.index'))
            ->assertInertiaFlash('status', 'تم تعديل التصنيف.');

        $this->assertSame('أقساط السيارة', $category->refresh()->name);
    }

    public function test_saving_a_category_with_its_own_name_is_allowed(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);

        $this->actingAs($user)
            ->put(route('categories.update', $category), ['name' => 'أقساط'])
            ->assertSessionHasNoErrors();
    }

    public function test_update_does_not_change_the_type(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->ownedBy($user)->create();

        $this->actingAs($user)->put(route('categories.update', $category), ['name' => 'جديد', 'type' => 'income']);

        $this->assertSame(TransactionType::Expense, $category->refresh()->type);
    }

    public function test_rename_rejects_a_name_that_repeats_a_default_category(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'أكل']);
        $category = Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);

        $this->actingAs($user)
            ->put(route('categories.update', $category), ['name' => 'أكل'])
            ->assertSessionHasErrors(['name' => 'هذا التصنيف موجود من قبل.']);

        $this->assertSame('أقساط', $category->refresh()->name);
    }

    public function test_rename_rejects_an_empty_name(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);

        $this->actingAs($user)
            ->put(route('categories.update', $category), ['name' => ''])
            ->assertSessionHasErrors(['name' => 'اكتب اسم التصنيف.']);

        $this->assertSame('أقساط', $category->refresh()->name);
    }

    public function test_another_user_gets_not_found_from_renaming_a_category(): void
    {
        $category = Category::factory()->ownedBy(User::factory()->create())->create(['name' => 'أقساط']);

        $this->actingAs(User::factory()->create())
            ->put(route('categories.update', $category), ['name' => 'مسروق'])
            ->assertNotFound();

        $this->assertSame('أقساط', $category->refresh()->name);
    }

    public function test_default_category_cannot_be_renamed(): void
    {
        $category = Category::factory()->create(['name' => 'أكل']);

        $this->actingAs(User::factory()->create())
            ->put(route('categories.update', $category), ['name' => 'طعام'])
            ->assertForbidden();

        $this->assertSame('أكل', $category->refresh()->name);
    }

    // Destroy

    public function test_owner_can_delete_an_unused_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->ownedBy($user)->create();

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertInertiaFlash('status', 'تم حذف التصنيف.');

        $this->assertModelMissing($category);
    }

    public function test_category_used_by_transactions_is_not_deleted(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);
        Transaction::factory()->count(2)->for($user)->for($category)->create();

        $this->actingAs($user)
            ->delete(route('categories.destroy', $category))
            ->assertRedirect(route('categories.index'))
            ->assertSessionHasErrors([
                'category' => 'لا يمكن حذف «أقساط» لأنه مستخدم في 2 حركة. غيّر تصنيف هذه الحركات أولًا.',
            ]);

        $this->assertModelExists($category);
    }

    public function test_list_shows_why_a_category_was_not_deleted(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);
        Transaction::factory()->for($user)->for($category)->create();

        $this->actingAs($user)
            ->followingRedirects()
            ->delete(route('categories.destroy', $category))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('categories/index')
                ->where('errors.category', fn (string $message) => str_starts_with($message, 'لا يمكن حذف «أقساط» لأنه مستخدم في 1 حركة.')));
    }

    public function test_another_user_gets_not_found_from_deleting_a_category(): void
    {
        $category = Category::factory()->ownedBy(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('categories.destroy', $category))
            ->assertNotFound();

        $this->assertModelExists($category);
    }

    public function test_default_category_cannot_be_deleted(): void
    {
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('categories.destroy', $category))
            ->assertForbidden();

        $this->assertModelExists($category);
    }

    public function test_guest_cannot_delete_a_category(): void
    {
        $category = Category::factory()->ownedBy(User::factory()->create())->create();

        $this->delete(route('categories.destroy', $category))->assertRedirect(route('login'));

        $this->assertModelExists($category);
    }
}
