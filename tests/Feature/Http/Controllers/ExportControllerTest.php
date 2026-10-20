<?php

namespace Tests\Feature\Http\Controllers;

use App\Enums\DebtDirection;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The downloaded file's rows, without the byte order mark.
     *
     * @return list<list<string>>
     */
    private function exportedRows(): array
    {
        $content = $this->get(route('export'))->assertOk()->streamedContent();

        $this->assertStringStartsWith("\u{FEFF}", $content);

        $lines = explode("\n", trim(substr($content, strlen("\u{FEFF}"))));

        return array_map(fn (string $line): array => str_getcsv($line, escape: ''), $lines);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('export'))->assertRedirect(route('login'));
    }

    public function test_downloads_a_dated_csv_file(): void
    {
        Carbon::setTestNow('2026-10-20 09:00');
        $this->signIn();

        $this->get(route('export'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertDownload('masarifi-2026-10-20.csv');
    }

    public function test_lists_the_users_transactions_oldest_first_with_arabic_labels(): void
    {
        Transaction::factory()->create(['note' => 'حركة شخص آخر']);
        $user = $this->signIn();
        $food = Category::factory()->create(['name' => 'أكل']);
        $expense = Transaction::factory()->for($user)->for($food)
            ->create(['amount' => '12.50', 'note' => 'غداء', 'occurred_on' => '2026-10-05']);
        $debt = Transaction::factory()->for($user)->debt(DebtDirection::OwedToMe)
            ->create(['amount' => '100.00', 'counterparty' => 'سامي', 'note' => null, 'occurred_on' => '2026-10-01']);
        $repayment = $this->repayAsOwner($debt, ['occurred_on' => '2026-10-09']);

        $this->assertSame([
            ['الرقم', 'التاريخ', 'النوع', 'التصنيف', 'المبلغ', 'الشخص', 'اتجاه الدين', 'تاريخ السداد', 'سداد للدين رقم', 'الملاحظة'],
            [(string) $debt->id, '2026-10-01', 'دين', '', '100.00', 'سامي', 'إلي', '2026-10-09', '', ''],
            [(string) $expense->id, '2026-10-05', 'مصروف', 'أكل', '12.50', '', '', '', '', 'غداء'],
            [(string) $repayment->id, '2026-10-09', 'قبض', '', '100.00', 'سامي', '', '', (string) $debt->id, ''],
        ], $this->exportedRows());
    }

    public function test_keeps_typed_text_from_running_as_a_spreadsheet_formula(): void
    {
        $user = $this->signIn();
        Transaction::factory()->for($user)->create(['note' => '=HYPERLINK("http://evil.test")', 'counterparty' => '+1']);

        $row = $this->exportedRows()[1];

        $this->assertSame("'+1", $row[5]);
        $this->assertSame('\'=HYPERLINK("http://evil.test")', $row[9]);
    }
}
