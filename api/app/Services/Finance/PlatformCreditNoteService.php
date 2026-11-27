<?php

namespace App\Services\Finance;

use App\Models\PlatformCreditNote;
use App\Models\PlatformInvoice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlatformCreditNoteService
{
    public function paginate(
        array $filters = []
    ): LengthAwarePaginator {

        $query = PlatformCreditNote::query();

        if (!empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        if (!empty($filters['platform_invoice_id'])) {
            $query->where(
                'platform_invoice_id',
                $filters['platform_invoice_id']
            );
        }

        if (!empty($filters['currency'])) {
            $query->where(
                'currency',
                strtolower($filters['currency'])
            );
        }

        $query->whereHas(
            'invoice',
            function ($query) use ($filters) {

                $query->where(
                    'livemode',
                    filter_var(
                        $filters['livemode'] ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    )
                );
            }
        );

        return $query
            ->latest('id')
            ->paginate(
                max(
                    1,
                    min(
                        (int) ($filters['per_page'] ?? 25),
                        100
                    )
                )
            );
    }

    public function find(int $id): PlatformCreditNote
    {
        return PlatformCreditNote::findOrFail($id);
    }

    public function createDraft(
        int $invoiceId,
        array $data,
        int $userId
    ): PlatformCreditNote {

        $validated = Validator::make($data, [

            'amount_minor' => [
                'required',
                'integer',
                'min:1',
                'max:999999999999',
            ],

            'tax_minor' => [
                'nullable',
                'integer',
                'min:0',
                'lte:amount_minor',
            ],

            'reason' => [
                'required',
                'string',
                'max:5000',
            ],

        ])->validate();

        return DB::transaction(function () use (
            $invoiceId,
            $validated,
            $userId
        ) {

            $invoice = PlatformInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoiceId);

            $this->ensureEligible($invoice);

            $this->validateAmount(
                $invoice,
                (int) $validated['amount_minor'],
                (int) ($validated['tax_minor'] ?? 0)
            );

            return PlatformCreditNote::create([

                'platform_invoice_id' => $invoice->id,

                'created_by' => $userId,

                'credit_note_number' => 'CN-'
                    . now()->format('Ymd')
                    . '-'
                    . strtoupper((string) Str::ulid()),

                'amount_minor' =>
                    $validated['amount_minor'],

                'tax_minor' =>
                    $validated['tax_minor'] ?? 0,

                'currency' => $invoice->currency,

                'status' => 'draft',

                'reason' => $validated['reason'],
            ]);

        }, 3);
    }

    public function issue(int $id): PlatformCreditNote
    {
        return DB::transaction(function () use ($id) {

            $record = PlatformCreditNote::findOrFail($id);

            $invoice = PlatformInvoice::query()
                ->lockForUpdate()
                ->findOrFail(
                    $record->platform_invoice_id
                );

            $creditNote = PlatformCreditNote::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($creditNote->status === 'issued') {
                return $creditNote;
            }

            if ($creditNote->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft credit notes can be issued.',
                ]);
            }

            $this->ensureEligible($invoice);

            $this->validateAmount(
                $invoice,
                $creditNote->amount_minor,
                $creditNote->tax_minor
            );

            $creditNote->update([
                'status' => 'issued',
                'issued_at' => now(),
            ]);

            $amountDue =
                $invoice->amount_due_minor
                - $creditNote->amount_minor;

            $invoice->update([

                'amount_due_minor' => $amountDue,

                'status' => $amountDue === 0
                    ? 'paid'
                    : (
                        $invoice->amount_paid_minor > 0
                            ? 'partially_paid'
                            : 'open'
                    ),

                'paid_at' => $amountDue === 0
                    ? now()
                    : null,
            ]);

            return $creditNote->refresh();

        }, 3);
    }

    public function voidDraft(
        int $id
    ): PlatformCreditNote {

        return DB::transaction(function () use ($id) {

            $creditNote = PlatformCreditNote::query()
                ->lockForUpdate()
                ->findOrFail($id);

            if ($creditNote->status !== 'draft') {
                throw ValidationException::withMessages([
                    'status' =>
                        'Only draft credit notes can be voided locally.',
                ]);
            }

            $creditNote->update([
                'status' => 'void',
                'voided_at' => now(),
            ]);

            return $creditNote->refresh();

        }, 3);
    }

    private function ensureEligible(
        PlatformInvoice $invoice
    ): void {

        if (
            !in_array(
                $invoice->status,
                ['open', 'partially_paid', 'overdue'],
                true
            )
            || $invoice->stripe_invoice_id !== null
        ) {
            throw ValidationException::withMessages([
                'invoice' =>
                    'This invoice is not eligible for a local credit note.',
            ]);
        }
    }

    private function validateAmount(
        PlatformInvoice $invoice,
        int $amount,
        int $tax
    ): void {

        $issuedTax = (int) $invoice->creditNotes()
            ->where('status', 'issued')
            ->sum('tax_minor');

        if (
            $amount > $invoice->amount_due_minor
            || $tax > $amount
            || $tax > max(
                0,
                $invoice->tax_minor - $issuedTax
            )
        ) {
            throw ValidationException::withMessages([
                'amount_minor' =>
                    'The credit note exceeds the available invoice balance or tax amount.',
            ]);
        }
    }
}