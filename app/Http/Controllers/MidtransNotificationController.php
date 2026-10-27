<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use App\Services\MidtransService;
use App\Services\OrderReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class MidtransNotificationController extends Controller
{
    public function __invoke(
        Request $request,
        MidtransService $midtrans,
        OrderReservationService $reservations,
    ): JsonResponse {
        $notification = $request->all();

        try {
            $status = $midtrans->verifyNotification($notification);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Midtrans notification could not be authenticated.', [
                'reason' => $exception->getMessage(),
                'order_id' => $notification['order_id'] ?? null,
            ]);

            return response()->json(['message' => 'Invalid notification.'], 403);
        } catch (Throwable $exception) {
            Log::error('Midtrans status verification failed.', [
                'order_id' => $notification['order_id'] ?? null,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Status verification failed.'], 503);
        }

        $order = Order::query()
            ->where('public_id', $notification['order_id'] ?? '')
            ->first();

        if ($order === null) {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        if (
            ($status['order_id'] ?? null) !== $order->public_id
            || ! $midtrans->rupiahAmountMatches($status['gross_amount'] ?? null, $order->total)
            || (string) ($status['status_code'] ?? '') !== '200'
        ) {
            Log::warning('Midtrans status did not match the local order.', [
                'order_id' => $order->public_id,
            ]);

            return response()->json(['message' => 'Transaction details do not match.'], 422);
        }

        DB::transaction(function () use ($order, $status, $midtrans, $reservations): void {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);
            $transactionId = is_string($status['transaction_id'] ?? null)
                ? $status['transaction_id']
                : null;

            if ($lockedOrder->status === 'paid') {
                if ($lockedOrder->gateway_transaction_id === null && $transactionId !== null) {
                    $lockedOrder->forceFill(['gateway_transaction_id' => $transactionId])->save();
                }

                return;
            }

            if ($lockedOrder->status !== 'pending') {
                if ($midtrans->paidStatus($status)) {
                    $lockedOrder->forceFill([
                        'gateway_transaction_id' => $transactionId,
                        'late_gateway_payment_at' => $lockedOrder->late_gateway_payment_at ?? now(),
                    ])->save();

                    Log::critical('Payment arrived after the ticket reservation was released; refund review is required.', [
                        'order_id' => $lockedOrder->public_id,
                        'transaction_id' => $transactionId,
                    ]);
                }

                return;
            }

            if ($midtrans->paidStatus($status)) {
                if ($lockedOrder->expires_at === null || ! $lockedOrder->expires_at->isFuture()) {
                    $reservations->expireLocked($lockedOrder);
                    $lockedOrder->forceFill([
                        'gateway_transaction_id' => $transactionId,
                        'late_gateway_payment_at' => now(),
                    ])->save();

                    Log::critical('Payment arrived after the ticket reservation expired; refund review is required.', [
                        'order_id' => $lockedOrder->public_id,
                        'transaction_id' => $transactionId,
                    ]);

                    return;
                }

                $lockedOrder->forceFill([
                    'status' => 'paid',
                    'payment_method' => 'midtrans',
                    'gateway_transaction_id' => $transactionId,
                    'paid_at' => now(),
                ])->save();

                return;
            }

            if (in_array($status['transaction_status'] ?? null, ['expire', 'cancel', 'deny'], true)) {
                $ticket = Ticket::query()
                    ->lockForUpdate()
                    ->findOrFail($lockedOrder->ticket_id);
                $ticket->increment('stock', $lockedOrder->quantity);
                $lockedOrder->forceFill([
                    'status' => 'expired',
                    'expired_at' => now(),
                    'gateway_transaction_id' => $transactionId,
                ])->save();
            }
        }, attempts: 3);

        return response()->json(['message' => 'Notification processed.']);
    }
}
