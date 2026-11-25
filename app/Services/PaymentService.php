<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PaymentService
{
    public function recordPayment(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $member = Member::lockForUpdate()->findOrFail($data['member_id']);
            $subscription = null;

            if (!empty($data['subscription_id'])) {
                $subscription = Subscription::lockForUpdate()->findOrFail($data['subscription_id']);
                if ((int) $subscription->member_id !== (int) $member->id) {
                    throw new InvalidArgumentException('Selected membership does not belong to the chosen member.');
                }
            }

            $amountPaid = round((float) ($data['amount_paid'] ?? $data['amount'] ?? 0), 2);
            if ($amountPaid < 0) {
                throw new InvalidArgumentException('Payment amount cannot be negative.');
            }

            $totalAmount = isset($data['total_amount']) && $data['total_amount'] !== ''
                ? round((float) $data['total_amount'], 2)
                : ($subscription ? round($subscription->remainingBalance() > 0 ? $subscription->remainingBalance() : $subscription->effectivePrice(), 2) : $amountPaid);

            if ($totalAmount <= 0 && $amountPaid <= 0) {
                throw new InvalidArgumentException('Total amount must be greater than zero.');
            }

            if ($amountPaid > $totalAmount && $totalAmount > 0) {
                throw new InvalidArgumentException('Amount paid cannot exceed total due (' . number_format($totalAmount, 2) . ').');
            }

            // Duplicate submission guard within a 15-second window for identical payload
            $recentDuplicate = Payment::where('member_id', $member->id)
                ->where('subscription_id', $subscription?->id)
                ->where('amount_paid', $amountPaid)
                ->whereDate('payment_date', Carbon::parse($data['payment_date'])->toDateString())
                ->where('payment_method', $data['payment_method'])
                ->where('created_at', '>=', now()->subSeconds(15))
                ->exists();

            if ($recentDuplicate) {
                throw new InvalidArgumentException('Duplicate payment submission detected. Please verify recent payments before retrying.');
            }

            $remainingBalance = max(0, round($totalAmount - $amountPaid, 2));
            $status = $this->determineStatus($amountPaid, $remainingBalance, $data['status'] ?? null);

            if ($status === Payment::STATUS_PENDING) {
                $amountPaid = 0.00;
                $remainingBalance = $totalAmount;
            }

            $payment = Payment::create([
                'payment_reference' => Payment::generateReference(),
                'member_id' => $member->id,
                'subscription_id' => $subscription?->id,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'remaining_balance' => $remainingBalance,
                'amount' => $amountPaid,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'transaction_id' => $data['transaction_id'] ?? null,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);

            // If this payment settles an earlier pending/partially paid record on the same subscription,
            // clear its open balance without altering its historical amount_paid or payment_date.
            if ($subscription && $amountPaid > 0) {
                $this->reconcileOpenSubscriptionBalances($subscription, $payment);
            }

            ActivityLog::record(
                'payment.recorded',
                "Recorded {$status} payment {$payment->payment_reference} of " . number_format($amountPaid, 2) . " for {$member->name}.",
                $payment,
                [
                    'member_id' => $member->id,
                    'subscription_id' => $subscription?->id,
                    'total_amount' => $totalAmount,
                    'amount_paid' => $amountPaid,
                    'remaining_balance' => $remainingBalance,
                ]
            );

            return $payment;
        });
    }

    public function recordInstallment(Payment $existingPayment, array $data): Payment
    {
        return DB::transaction(function () use ($existingPayment, $data) {
            $locked = Payment::lockForUpdate()->findOrFail($existingPayment->id);

            if ((float) $locked->remaining_balance <= 0) {
                throw new InvalidArgumentException('This payment has no remaining balance.');
            }

            $installmentAmount = round((float) $data['amount_paid'], 2);
            if ($installmentAmount <= 0 || $installmentAmount > (float) $locked->remaining_balance) {
                throw new InvalidArgumentException('Installment amount must be between 0.01 and ' . number_format((float) $locked->remaining_balance, 2) . '.');
            }

            $newRemaining = max(0, round((float) $locked->remaining_balance - $installmentAmount, 2));
            $newPayment = Payment::create([
                'payment_reference' => Payment::generateReference(),
                'member_id' => $locked->member_id,
                'subscription_id' => $locked->subscription_id,
                'total_amount' => (float) $locked->remaining_balance,
                'amount_paid' => $installmentAmount,
                'remaining_balance' => $newRemaining,
                'amount' => $installmentAmount,
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'transaction_id' => $data['transaction_id'] ?? null,
                'status' => $newRemaining > 0 ? Payment::STATUS_PARTIALLY_PAID : Payment::STATUS_PAID,
                'notes' => trim(($data['notes'] ?? '') . " (Installment against {$locked->payment_reference})"),
                'recorded_by' => auth()->id(),
            ]);

            $locked->update([
                'remaining_balance' => 0,
                'notes' => trim(($locked->notes ? $locked->notes . "\n" : '') . "Balance carried/settled via {$newPayment->payment_reference}"),
            ]);

            ActivityLog::record(
                'payment.installment',
                "Recorded balance payment {$newPayment->payment_reference} (" . number_format($installmentAmount, 2) . ") clearing {$locked->payment_reference}.",
                $newPayment,
                ['parent_payment_id' => $locked->id, 'amount_paid' => $installmentAmount]
            );

            return $newPayment;
        });
    }

    public function updatePermittedDetails(Payment $payment, array $data): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $locked = Payment::lockForUpdate()->findOrFail($payment->id);

            $locked->update([
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'transaction_id' => $data['transaction_id'] ?? $locked->transaction_id,
                'notes' => $data['notes'] ?? $locked->notes,
            ]);

            ActivityLog::record(
                'payment.updated',
                "Updated metadata for payment {$locked->payment_reference}.",
                $locked,
                ['payment_method' => $locked->payment_method, 'payment_date' => $locked->payment_date?->toDateString()]
            );

            return $locked;
        });
    }

    public function refundPayment(Payment $payment, float $refundAmount, string $reason): Payment
    {
        return DB::transaction(function () use ($payment, $refundAmount, $reason) {
            $locked = Payment::lockForUpdate()->findOrFail($payment->id);
            $net = $locked->netAmount();

            if ($refundAmount <= 0 || $refundAmount > $net) {
                throw new InvalidArgumentException('Refund amount cannot exceed net paid amount (' . number_format($net, 2) . ').');
            }

            $totalRefunded = round((float) $locked->refunded_amount + $refundAmount, 2);
            $fullyRefunded = $totalRefunded >= (float) $locked->amount_paid;

            $locked->update([
                'refunded_amount' => $totalRefunded,
                'refunded_at' => now(),
                'refund_reason' => $reason,
                'status' => $fullyRefunded ? Payment::STATUS_REFUNDED : $locked->status,
            ]);

            ActivityLog::record(
                'payment.refunded',
                "Refunded " . number_format($refundAmount, 2) . " on payment {$locked->payment_reference}. Reason: {$reason}",
                $locked,
                ['refund_amount' => $refundAmount, 'total_refunded' => $totalRefunded, 'reason' => $reason]
            );

            return $locked;
        });
    }

    private function reconcileOpenSubscriptionBalances(Subscription $subscription, Payment $latestPayment): void
    {
        $priorOpen = Payment::where('subscription_id', $subscription->id)
            ->where('id', '!=', $latestPayment->id)
            ->whereIn('status', [Payment::STATUS_PENDING, Payment::STATUS_PARTIALLY_PAID])
            ->where('remaining_balance', '>', 0)
            ->lockForUpdate()
            ->get();

        foreach ($priorOpen as $old) {
            $old->update([
                'remaining_balance' => 0,
                'notes' => trim(($old->notes ? $old->notes . "\n" : '') . "Balance superseded by {$latestPayment->payment_reference}"),
            ]);
        }
    }

    private function determineStatus(float $amountPaid, float $remainingBalance, ?string $requested): string
    {
        if ($requested === Payment::STATUS_PENDING && $amountPaid <= 0) {
            return Payment::STATUS_PENDING;
        }
        if ($amountPaid <= 0) {
            return Payment::STATUS_PENDING;
        }
        if ($remainingBalance > 0) {
            return Payment::STATUS_PARTIALLY_PAID;
        }

        return Payment::STATUS_PAID;
    }
}
