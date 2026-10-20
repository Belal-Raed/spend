<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const HEADINGS = ['الرقم', 'التاريخ', 'النوع', 'التصنيف', 'المبلغ', 'الشخص', 'اتجاه الدين', 'تاريخ السداد', 'سداد للدين رقم', 'الملاحظة'];

    /**
     * Download all of the user's transactions as a CSV file, oldest first.
     *
     * Always available, so users keep their data even when their account is read-only.
     */
    public function __invoke(Request $request): StreamedResponse
    {
        $user = $request->user();

        return response()->streamDownload(
            fn () => $this->writeCsv($user),
            'masarifi-'.today()->toDateString().'.csv',
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    private function writeCsv(User $user): void
    {
        $output = fopen('php://output', 'w');

        // Excel reads the file as UTF-8, and shows the Arabic correctly, only after this byte order mark.
        fwrite($output, "\u{FEFF}");
        fputcsv($output, self::HEADINGS, escape: '');

        $transactions = $user->transactions()
            ->with('category:id,name')
            ->orderBy('occurred_on')
            ->orderBy('id')
            ->lazy();

        foreach ($transactions as $transaction) {
            fputcsv($output, $this->row($transaction), escape: '');
        }

        fclose($output);
    }

    /**
     * @return list<string>
     */
    private function row(Transaction $transaction): array
    {
        return [
            (string) $transaction->id,
            $transaction->occurred_on->toDateString(),
            $transaction->type->label(),
            $this->text($transaction->category?->name),
            $transaction->amount,
            $this->text($transaction->counterparty),
            $transaction->debt_direction?->label() ?? '',
            $transaction->settled_on?->toDateString() ?? '',
            (string) $transaction->settles_id,
            $this->text($transaction->note),
        ];
    }

    /**
     * Text the user typed, kept from running as a formula when the file is opened in a spreadsheet.
     */
    private function text(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return in_array(mb_substr($value, 0, 1), ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$value : $value;
    }
}
