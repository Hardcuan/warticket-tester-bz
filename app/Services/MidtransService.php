<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransService
{
    /**
     * @return array{token: string, redirect_url: string}
     */
    public function createSnapSession(Order $order): array
    {
        $serverKey = (string) config('services.midtrans.server_key');

        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        $order->loadMissing('user');

        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->asJson()
            ->connectTimeout(5)
            ->timeout(15)
            ->post(config('services.midtrans.snap_url'), [
                'transaction_details' => [
                    'order_id' => $order->public_id,
                    'gross_amount' => $order->total,
                ],
                'item_details' => [[
                    'id' => (string) $order->ticket_id,
                    'price' => $order->unit_price,
                    'quantity' => $order->quantity,
                    'name' => mb_substr($order->ticket_name, 0, 50),
                    'merchant_name' => config('app.name'),
                ]],
                'customer_details' => [
                    'first_name' => $order->user->name,
                    'email' => $order->user->email,
                ],
                'expiry' => [
                    'start_time' => now()->timezone('Asia/Jakarta')->format('Y-m-d H:i:s O'),
                    'unit' => 'day',
                    'duration' => 1,
                ],
                'page_expiry' => [
                    'duration' => 1,
                    'unit' => 'day',
                ],
            ])
            ->throw();

        $result = $response->json();
        $redirectUrl = is_array($result) ? ($result['redirect_url'] ?? null) : null;
        $token = is_array($result) ? ($result['token'] ?? null) : null;
        $host = is_string($redirectUrl) ? parse_url($redirectUrl, PHP_URL_HOST) : null;
        $allowedHosts = config('services.midtrans.is_production')
            ? ['app.midtrans.com']
            : ['app.sandbox.midtrans.com'];

        if (
            ! is_string($token)
            || $token === ''
            || ! is_string($redirectUrl)
            || parse_url($redirectUrl, PHP_URL_SCHEME) !== 'https'
            || ! in_array($host, $allowedHosts, true)
        ) {
            throw new RuntimeException('Midtrans memberikan respons sesi pembayaran yang tidak valid.');
        }

        return ['token' => $token, 'redirect_url' => $redirectUrl];
    }

    /**
     * @param  array<string, mixed>  $notification
     * @return array<string, mixed>
     *
     * @throws ConnectionException
     */
    public function verifyNotification(array $notification): array
    {
        $serverKey = (string) config('services.midtrans.server_key');
        $orderId = $notification['order_id'] ?? null;
        $statusCode = $notification['status_code'] ?? null;
        $grossAmount = $notification['gross_amount'] ?? null;
        $signature = $notification['signature_key'] ?? null;

        if ($serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        if (
            ! is_string($orderId)
            || ! is_string($statusCode)
            || ! is_string($grossAmount)
            || ! is_string($signature)
        ) {
            throw new \InvalidArgumentException('Notifikasi Midtrans tidak lengkap atau konfigurasi server key kosong.');
        }

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if (! hash_equals($expectedSignature, $signature)) {
            throw new \InvalidArgumentException('Signature notifikasi Midtrans tidak valid.');
        }

        $statusUrl = rtrim((string) config('services.midtrans.status_url'), '/')
            .'/'.rawurlencode($orderId).'/status';

        $status = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->get($statusUrl)
            ->throw()
            ->json();

        if (! is_array($status)) {
            throw new RuntimeException('Midtrans status API returned an invalid response.');
        }

        return $status;
    }

    public function paidStatus(array $status): bool
    {
        if (($status['transaction_status'] ?? null) === 'settlement') {
            return true;
        }

        return ($status['transaction_status'] ?? null) === 'capture'
            && strtolower((string) ($status['fraud_status'] ?? '')) === 'accept';
    }

    public function rupiahAmountMatches(mixed $amount, int $expected): bool
    {
        if (! is_string($amount) || ! preg_match('/^(\d+)(?:\.(\d{1,2}))?$/', $amount, $parts)) {
            return false;
        }

        if (isset($parts[2]) && trim($parts[2], '0') !== '') {
            return false;
        }

        $integer = filter_var($parts[1], FILTER_VALIDATE_INT);

        return $integer !== false && $integer === $expected;
    }
}
