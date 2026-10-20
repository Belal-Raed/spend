<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DebtDirection;
use App\Enums\TransactionType;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function expensePayload(Category $category, array $overrides = []): array
    {
        return [
            'type' => 'expense',
            'amount' => '45.50',
            'category_id' => $category->id,
            'occurred_on' => '2026-10-10',
            'note' => 'غداء',
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function debtPayload(array $overrides = []): array
    {
        return [
            'type' => 'debt',
            'amount' => '500',
            'counterparty' => 'خالد',
            'debt_direction' => 'owed_by_me',
            'occurred_on' => '2026-10-10',
            ...$overrides,
        ];
    }

    private function debt(User $user, string $amount = '500.00'): Transaction
    {
        return Transaction::factory()->for($user)->debt(DebtDirection::OwedByMe)->create(['amount' => $amount]);
    }

    /**
     * @return list<int>
     */
    private function listedIds(mixed $response): array
    {
        return $response->viewData('transactions')->pluck('id')->all();
    }

    // Index

    public function test_guest_is_redirected_to_login_from_the_list(): void
    {
        $this->get(route('transactions.index'))->assertRedirect(route('login'));
    }

    public function test_lists_the_users_transactions_newest_first(): void
    {
        $user = User::factory()->create();
        $older = Transaction::factory()->for($user)->create(['occurred_on' => '2026-10-01']);
        $newer = Transaction::factory()->for($user)->create(['occurred_on' => '2026-10-05']);
        $sameDayButRecordedLater = Transaction::factory()->for($user)->create(['occurred_on' => '2026-10-05']);

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $this->assertSame([$sameDayButRecordedLater->id, $newer->id, $older->id], $this->listedIds($response));
    }

    public function test_does_not_list_another_users_transactions(): void
    {
        Transaction::factory()->create(['note' => 'حركة شخص آخر']);

        $this->actingAs(User::factory()->create())
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSeeText('لا توجد حركات.')
            ->assertDontSeeText('حركة شخص آخر');
    }

    public function test_shows_each_kind_of_transaction_with_a_signed_amount(): void
    {
        $user = User::factory()->create();
        $food = Category::factory()->create(['name' => 'أكل']);
        $salary = Category::factory()->income()->create(['name' => 'راتب']);
        Transaction::factory()->for($user)->for($food)->create(['amount' => '45.50', 'occurred_on' => '2026-10-04']);
        Transaction::factory()->for($user)->income()->create([
            'category_id' => $salary->id, 'amount' => '3000.00', 'occurred_on' => '2026-10-03',
        ]);
        $debt = Transaction::factory()->for($user)->debt(DebtDirection::OwedToMe)->create([
            'amount' => '200.00', 'counterparty' => 'سامي', 'occurred_on' => '2026-10-02',
        ]);
        app(TransactionService::class)->repayDebt($debt, ['occurred_on' => '2026-10-05']);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertSeeTextInOrder([
                'سداد دين · سامي', '200.00',
                'مصروف · أكل', '-45.50',
                'قبض · راتب', '3,000.00',
                'دين إلي · سامي', 'انسدّ', '-200.00',
            ]);
    }

    public function test_filters_the_list_by_period(): void
    {
        $this->travelTo('2026-10-12');
        $user = User::factory()->create();
        $thisMonth = Transaction::factory()->for($user)->create(['occurred_on' => '2026-10-01']);
        Transaction::factory()->for($user)->create(['occurred_on' => '2026-09-30']);

        $response = $this->actingAs($user)->get(route('transactions.index', ['period' => 'this_month']));

        $this->assertSame([$thisMonth->id], $this->listedIds($response));
    }

    public function test_filters_the_list_by_category(): void
    {
        $user = User::factory()->create();
        $food = Category::factory()->create();
        $inFood = Transaction::factory()->for($user)->for($food)->create();
        Transaction::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', ['category' => $food->id]));

        $this->assertSame([$inFood->id], $this->listedIds($response));
    }

    public function test_ignores_a_period_and_category_that_do_not_exist(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', ['period' => 'next_decade', 'category' => 999]));

        $response->assertOk();
        $this->assertSame([$transaction->id], $this->listedIds($response));
    }

    public function test_ignores_a_filter_on_another_users_category(): void
    {
        $user = User::factory()->create();
        $othersCategory = Category::factory()->ownedBy(User::factory()->create())->create(['name' => 'تصنيف خاص']);
        $transaction = Transaction::factory()->for($user)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', ['category' => $othersCategory->id]));

        $this->assertSame([$transaction->id], $this->listedIds($response));
        $response->assertDontSeeText('تصنيف خاص');
    }

    public function test_shows_twenty_transactions_per_page_and_keeps_the_filter_in_the_next_link(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Transaction::factory()->count(21)->for($user)->for($category)->create();

        $response = $this->actingAs($user)->get(route('transactions.index', ['category' => $category->id]));

        $this->assertCount(20, $this->listedIds($response));
        $response->assertSee('category='.$category->id.'&amp;page=2', escape: false);
    }

    public function test_escapes_dangerous_content_in_a_note(): void
    {
        $user = User::factory()->create();
        Transaction::factory()->for($user)->create(['note' => "<script>alert('xss')</script>"]);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee("<script>alert('xss')</script>", escape: false);
    }

    // Create

    public function test_guest_is_redirected_to_login_from_the_new_transaction_form(): void
    {
        $this->get(route('transactions.create'))->assertRedirect(route('login'));
    }

    public function test_new_transaction_form_defaults_to_an_expense_with_the_categories_the_user_can_pick(): void
    {
        $user = User::factory()->create();
        $default = Category::factory()->create();
        $own = Category::factory()->ownedBy($user)->create();
        Category::factory()->income()->create();
        Category::factory()->ownedBy(User::factory()->create())->create();

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $response->assertOk()->assertViewHas('type', TransactionType::Expense);
        $this->assertEqualsCanonicalizing([$default->id, $own->id], $response->viewData('categories')->pluck('id')->all());
    }

    public function test_new_transaction_form_lists_categories_by_name_with_the_catch_all_last(): void
    {
        $user = User::factory()->create();
        Category::factory()->create(['name' => 'أخرى']);
        Category::factory()->create(['name' => 'مواصلات']);
        Category::factory()->ownedBy($user)->create(['name' => 'أقساط']);

        $response = $this->actingAs($user)->get(route('transactions.create'));

        $this->assertSame(['أقساط', 'مواصلات', 'أخرى'], $response->viewData('categories')->pluck('name')->all());
    }

    public function test_income_form_offers_income_categories(): void
    {
        $salary = Category::factory()->income()->create();
        Category::factory()->create();

        $response = $this->actingAs(User::factory()->create())->get(route('transactions.create', ['type' => 'income']));

        $response->assertViewHas('type', TransactionType::Income);
        $this->assertSame([$salary->id], $response->viewData('categories')->pluck('id')->all());
    }

    public function test_debt_form_asks_for_a_direction_and_no_category(): void
    {
        Category::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('transactions.create', ['type' => 'debt']))
            ->assertOk()
            ->assertSee('name="debt_direction"', escape: false)
            ->assertDontSee('name="category_id"', escape: false);
    }

    public function test_unknown_type_falls_back_to_the_expense_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('transactions.create', ['type' => 'gift']))
            ->assertOk()
            ->assertViewHas('type', TransactionType::Expense);
    }

    // Store

    public function test_guest_cannot_record_a_transaction(): void
    {
        $this->post(route('transactions.store'), $this->expensePayload(Category::factory()->create()))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_valid_expense_is_recorded_for_the_user(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->post(route('transactions.store'), $this->expensePayload($category))
            ->assertRedirect(route('transactions.index'))
            ->assertInertiaFlash('status', 'تم تسجيل الحركة.');

        $transaction = $user->transactions()->sole();
        $this->assertSame(TransactionType::Expense, $transaction->type);
        $this->assertSame('45.50', $transaction->amount);
        $this->assertSame($category->id, $transaction->category_id);
        $this->assertSame('2026-10-10', $transaction->occurred_on->toDateString());
        $this->assertSame('غداء', $transaction->note);
    }

    public function test_valid_income_is_recorded_with_where_it_came_from(): void
    {
        $user = User::factory()->create();
        $salary = Category::factory()->income()->create();

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => 'income',
                'amount' => '3000',
                'category_id' => $salary->id,
                'counterparty' => 'الشركة',
                'occurred_on' => '2026-10-01',
            ])
            ->assertRedirect(route('transactions.index'));

        $transaction = $user->transactions()->sole();
        $this->assertSame(TransactionType::Income, $transaction->type);
        $this->assertSame('3000.00', $transaction->amount);
        $this->assertSame('الشركة', $transaction->counterparty);
    }

    public function test_valid_debt_is_recorded_with_its_direction_and_no_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $this->actingAs($user)
            ->post(route('transactions.store'), $this->debtPayload(['category_id' => $category->id]))
            ->assertRedirect(route('transactions.index'));

        $transaction = $user->transactions()->sole();
        $this->assertSame(TransactionType::Debt, $transaction->type);
        $this->assertSame(DebtDirection::OwedByMe, $transaction->debt_direction);
        $this->assertSame('خالد', $transaction->counterparty);
        $this->assertNull($transaction->category_id);
    }

    public function test_expense_ignores_a_counterparty_and_a_debt_direction(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('transactions.store'), $this->expensePayload(Category::factory()->create(), [
            'counterparty' => 'خالد',
            'debt_direction' => 'owed_by_me',
        ]));

        $transaction = $user->transactions()->sole();
        $this->assertNull($transaction->counterparty);
        $this->assertNull($transaction->debt_direction);
    }

    public function test_extra_fields_cannot_assign_the_transaction_to_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)->post(route('transactions.store'), $this->expensePayload(Category::factory()->create(), [
            'user_id' => $otherUser->id,
        ]));

        $this->assertSame(1, $user->transactions()->count());
        $this->assertSame(0, $otherUser->transactions()->count());
    }

    public function test_empty_form_is_rejected_with_a_message_for_each_field(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('transactions.store'), [])
            ->assertSessionHasErrors([
                'type' => 'اختر نوع الحركة.',
                'amount' => 'اكتب المبلغ.',
                'occurred_on' => 'اختر تاريخًا صحيحًا.',
                'category_id' => 'اختر تصنيفًا من القائمة.',
            ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_amount_must_be_more_than_zero(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('transactions.store'), $this->expensePayload(Category::factory()->create(), ['amount' => '0']))
            ->assertSessionHasErrors(['amount' => 'المبلغ يجب أن يكون أكبر من صفر.']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_amount_must_be_a_number(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('transactions.store'), $this->expensePayload(Category::factory()->create(), ['amount' => 'كثير']))
            ->assertSessionHasErrors(['amount' => 'المبلغ يجب أن يكون رقمًا.']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_expense_rejects_an_income_category(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('transactions.store'), $this->expensePayload(Category::factory()->income()->create()))
            ->assertSessionHasErrors(['category_id' => 'اختر تصنيفًا من القائمة.']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_rejects_another_users_category(): void
    {
        $othersCategory = Category::factory()->ownedBy(User::factory()->create())->create();

        $this->actingAs(User::factory()->create())
            ->post(route('transactions.store'), $this->expensePayload($othersCategory))
            ->assertSessionHasErrors(['category_id' => 'اختر تصنيفًا من القائمة.']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_debt_needs_a_counterparty_and_a_direction(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('transactions.store'), $this->debtPayload(['counterparty' => '', 'debt_direction' => '']))
            ->assertSessionHasErrors([
                'counterparty' => 'اكتب اسم الشخص.',
                'debt_direction' => 'اختر اتجاه الدين.',
            ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    // Edit

    public function test_owner_sees_the_edit_form_with_the_current_values(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create(['amount' => '45.50', 'note' => 'غداء']);

        $this->actingAs($user)
            ->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('value="45.50"', escape: false)
            ->assertSee('value="غداء"', escape: false);
    }

    public function test_another_user_is_forbidden_from_the_edit_form(): void
    {
        $transaction = Transaction::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('transactions.edit', $transaction))
            ->assertForbidden();
    }

    public function test_edit_form_for_a_debt_payment_has_no_category_or_counterparty_field(): void
    {
        $user = User::factory()->create();
        Category::factory()->create();
        $repayment = app(TransactionService::class)->repayDebt($this->debt($user), ['amount' => '100']);

        $this->actingAs($user)
            ->get(route('transactions.edit', $repayment))
            ->assertOk()
            ->assertDontSee('name="category_id"', escape: false)
            ->assertDontSee('name="counterparty"', escape: false);
    }

    // Update

    public function test_owner_can_change_a_transaction(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();
        $newCategory = Category::factory()->create();

        $this->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'amount' => '99.90',
                'category_id' => $newCategory->id,
                'occurred_on' => '2026-10-02',
                'note' => 'عشاء',
            ])
            ->assertRedirect(route('transactions.index'))
            ->assertInertiaFlash('status', 'تم تعديل الحركة.');

        $transaction->refresh();
        $this->assertSame('99.90', $transaction->amount);
        $this->assertSame($newCategory->id, $transaction->category_id);
        $this->assertSame('2026-10-02', $transaction->occurred_on->toDateString());
        $this->assertSame('عشاء', $transaction->note);
    }

    public function test_another_user_is_forbidden_from_changing_a_transaction(): void
    {
        $transaction = Transaction::factory()->create(['amount' => '45.50']);

        $this->actingAs(User::factory()->create())
            ->put(route('transactions.update', $transaction), [
                'amount' => '1',
                'category_id' => $transaction->category_id,
                'occurred_on' => '2026-10-02',
            ])
            ->assertForbidden();

        $this->assertSame('45.50', $transaction->refresh()->amount);
    }

    public function test_update_does_not_change_the_type(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $this->actingAs($user)->put(route('transactions.update', $transaction), [
            'type' => 'income',
            'amount' => '10',
            'category_id' => $transaction->category_id,
            'occurred_on' => '2026-10-02',
        ]);

        $this->assertSame(TransactionType::Expense, $transaction->refresh()->type);
    }

    public function test_update_rejects_invalid_values_with_a_message_for_each_field(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create(['amount' => '45.50']);

        $this->actingAs($user)
            ->put(route('transactions.update', $transaction), ['amount' => '', 'occurred_on' => 'not-a-date', 'category_id' => 999])
            ->assertSessionHasErrors([
                'amount' => 'اكتب المبلغ.',
                'occurred_on' => 'اختر تاريخًا صحيحًا.',
                'category_id' => 'اختر تصنيفًا من القائمة.',
            ]);

        $this->assertSame('45.50', $transaction->refresh()->amount);
    }

    public function test_debt_cannot_drop_below_what_was_already_paid(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user, '500.00');
        app(TransactionService::class)->repayDebt($debt, ['amount' => '200']);

        $this->actingAs($user)
            ->put(route('transactions.update', $debt), ['amount' => '150', 'counterparty' => 'خالد', 'occurred_on' => '2026-10-02'])
            ->assertSessionHasErrors(['amount' => 'المبلغ لا يمكن أن يقل عن المسدَّد من الدين (200.00).']);

        $this->assertSame('500.00', $debt->refresh()->amount);
    }

    public function test_debt_payment_cannot_grow_beyond_what_is_left_on_the_debt(): void
    {
        $user = User::factory()->create();
        $repayment = app(TransactionService::class)->repayDebt($this->debt($user, '500.00'), ['amount' => '200']);

        $this->actingAs($user)
            ->put(route('transactions.update', $repayment), ['amount' => '500.01', 'occurred_on' => '2026-10-02'])
            ->assertSessionHasErrors(['amount' => 'الدفعة لا يمكن أن تزيد عن المتبقي من الدين (500.00).']);

        $this->assertSame('200.00', $repayment->refresh()->amount);
    }

    public function test_raising_a_debt_payment_to_the_full_amount_settles_the_debt(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user, '500.00');
        $repayment = app(TransactionService::class)->repayDebt($debt, ['amount' => '200']);

        $this->actingAs($user)
            ->put(route('transactions.update', $repayment), ['amount' => '500', 'occurred_on' => '2026-10-02'])
            ->assertRedirect(route('transactions.index'));

        $this->assertTrue($debt->refresh()->isSettled());
    }

    // Destroy

    public function test_owner_can_delete_a_transaction(): void
    {
        $user = User::factory()->create();
        $transaction = Transaction::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('transactions.destroy', $transaction))
            ->assertRedirect(route('transactions.index'))
            ->assertInertiaFlash('status', 'تم حذف الحركة.');

        $this->assertModelMissing($transaction);
    }

    public function test_another_user_is_forbidden_from_deleting_a_transaction(): void
    {
        $transaction = Transaction::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('transactions.destroy', $transaction))
            ->assertForbidden();

        $this->assertModelExists($transaction);
    }

    public function test_deleting_a_debt_deletes_its_payments(): void
    {
        $user = User::factory()->create();
        $debt = $this->debt($user);
        $repayment = app(TransactionService::class)->repayDebt($debt, ['amount' => '100']);

        $this->actingAs($user)->delete(route('transactions.destroy', $debt));

        $this->assertModelMissing($debt);
        $this->assertModelMissing($repayment);
    }

    public function test_guest_cannot_delete_a_transaction(): void
    {
        $transaction = Transaction::factory()->create();

        $this->delete(route('transactions.destroy', $transaction))->assertRedirect(route('login'));

        $this->assertModelExists($transaction);
    }
}
