<?php

namespace App\Services\Finance;

use App\Models\PlatformTransaction;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportService
{
    public function __construct(
        private FinanceDashboardService $dashboard
    ) {
    }

    public function summary(
        string $from,
        string $to,
        string $currency = 'gbp',
        bool $livemode = false
    ): array {

        return $this->dashboard->overview(
            $currency,
            $livemode,
            $from,
            $to
        );
    }

    public function transactionBreakdown(
        string $from,
        string $to,
        string $currency = 'gbp',
        bool $livemode = false
    ): array {

        $start = CarbonImmutable::parse($from)
            ->startOfDay();

        $end = CarbonImmutable::parse($to)
            ->endOfDay();

        return PlatformTransaction::query()
            ->where('currency', strtolower($currency))
            ->where('livemode', $livemode)
            ->where('status', 'completed')
            ->whereBetween('occurred_at', [
                $start,
                $end,
            ])
            ->selectRaw("
                type,
                direction,
                COUNT(*) as transaction_count,
                SUM(amount_minor) as total_minor
            ")
            ->groupBy('type', 'direction')
            ->orderBy('type')
            ->get()
            ->map(function ($record) {

                return [
                    'type' => $record->type,

                    'direction' => $record->direction,

                    'transaction_count' =>
                        (int) $record->transaction_count,

                    'total_minor' =>
                        (int) $record->total_minor,
                ];
            })
            ->all();
    }

    public function exportTransactions(
        string $from,
        string $to,
        string $currency = 'gbp',
        bool $livemode = false
    ): StreamedResponse {

        $start = CarbonImmutable::parse($from)
            ->startOfDay();

        $end = CarbonImmutable::parse($to)
            ->endOfDay();

        $filename = 'ivision-transactions-'
            . $start->format('Ymd')
            . '-'
            . $end->format('Ymd')
            . '.csv';

        return response()->streamDownload(
            function () use (
                $start,
                $end,
                $currency,
                $livemode
            ) {

                $output = fopen('php://output', 'w');

                fputcsv($output, [
                    'Reference',
                    'Type',
                    'Direction',
                    'Amount (Minor Units)',
                    'Currency',
                    'Status',
                    'Description',
                    'Transaction Date',
                    'Reconciled At',
                    'Mode',
                ], ',', '"', '');

                PlatformTransaction::query()
                    ->where(
                        'currency',
                        strtolower($currency)
                    )
                    ->where('livemode', $livemode)
                    ->whereBetween('occurred_at', [
                        $start,
                        $end,
                    ])
                    ->chunkById(
                        500,
                        function ($transactions) use ($output) {

                            foreach ($transactions as $transaction) {

                                fputcsv($output, [

                                    $this->safeCell(
                                        $transaction->reference
                                    ),

                                    $this->safeCell(
                                        $transaction->type
                                    ),

                                    $transaction->direction,

                                    $transaction->amount_minor,

                                    strtoupper(
                                        $transaction->currency
                                    ),

                                    $transaction->status,

                                    $this->safeCell(
                                        $transaction->description
                                    ),

                                    $transaction->occurred_at
                                        ?->toDateTimeString(),

                                    $transaction->reconciled_at
                                        ?->toDateTimeString(),

                                    $transaction->livemode
                                        ? 'Live'
                                        : 'Test',

                                ], ',', '"', '');
                            }
                        }
                    );

                fclose($output);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, private',
            ]
        );
    }

    private function safeCell(?string $value): string
    {
        $value = $value ?? '';

        if (
            preg_match(
                '/^[\s\x00-\x1F]*[=+\-@]/u',
                $value
            )
            || preg_match('/^[\t\r\n]/', $value)
        ) {
            return "'" . $value;
        }

        return $value;
    }
}