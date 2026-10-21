<?php

namespace Tests\Feature\Filament\Resources\Payments;

use App\Enums\PaymentStatus;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_opens_on_every_users_receipts_waiting_for_review(): void
    {
        $pending = Payment::factory()->count(2)->create();
        $approved = Payment::factory()->approved()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListPayments::class)
            ->assertCanSeeTableRecords($pending)
            ->assertCanNotSeeTableRecords([$approved]);
    }

    public function test_approving_subscribes_the_user(): void
    {
        $payment = Payment::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListPayments::class)
            ->callAction(TestAction::make('approve')->table($payment))
            ->assertNotified('تم قبول الإشعار وتفعيل الاشتراك.');

        $this->assertSame(PaymentStatus::Approved, $payment->fresh()->status);
        $this->assertNotNull($payment->user->fresh()->subscription_ends_at);
    }

    public function test_rejecting_keeps_the_reason_for_the_user(): void
    {
        $payment = Payment::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListPayments::class)
            ->callAction(TestAction::make('reject')->table($payment), data: ['reason' => 'المبلغ ناقص.']);

        $this->assertSame(PaymentStatus::Rejected, $payment->fresh()->status);
        $this->assertSame('المبلغ ناقص.', $payment->fresh()->rejection_reason);
    }

    public function test_rejecting_needs_a_reason(): void
    {
        $payment = Payment::factory()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListPayments::class)
            ->callAction(TestAction::make('reject')->table($payment), data: ['reason' => ''])
            ->assertHasFormErrors(['reason' => 'required']);

        $this->assertTrue($payment->fresh()->isPending());
    }

    public function test_a_reviewed_receipt_cannot_be_reviewed_again(): void
    {
        $payment = Payment::factory()->approved()->create();
        $this->actingAs(User::factory()->admin()->create());

        Livewire::test(ListPayments::class, ['activeTab' => 'all'])
            ->assertActionHidden(TestAction::make('approve')->table($payment))
            ->assertActionHidden(TestAction::make('reject')->table($payment));
    }

    public function test_receipt_page_shows_the_image_through_a_link_that_expires(): void
    {
        Storage::fake('local', ['serve' => true]);
        $path = UploadedFile::fake()->image('receipt.jpg')->store('receipts/1', 'local');
        $payment = Payment::factory()->create(['receipt_path' => $path]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(PaymentResource::getUrl('view', ['record' => $payment]))
            ->assertOk()
            ->assertSee($payment->user->name)
            ->assertSee($path)
            // The fake disk marks its temporary links with the time they stop working.
            ->assertSee('expiration=', escape: false);
    }

    public function test_receipt_page_links_to_a_pdf(): void
    {
        Storage::fake('local', ['serve' => true]);
        $payment = Payment::factory()->create(['receipt_path' => 'receipts/1/receipt.pdf']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(PaymentResource::getUrl('view', ['record' => $payment]))
            ->assertOk()
            ->assertSee('فتح ملف الإشعار (PDF)');
    }

    public function test_user_who_is_not_an_admin_cannot_open_a_receipt(): void
    {
        $payment = Payment::factory()->create();

        $this->actingAs($payment->user)
            ->get(PaymentResource::getUrl('view', ['record' => $payment]))
            ->assertForbidden();
    }
}
