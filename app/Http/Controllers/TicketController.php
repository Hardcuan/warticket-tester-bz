<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Ticket;
use App\Services\MidtransService;
use App\Services\OrderReservationService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class TicketController extends Controller
{
    private const TEST_CAPTCHA_TEXT = 'QATEST';

    public function index(): View
    {
        $tickets = Ticket::query()
            ->where('is_published', true)
            ->orderBy('sales_starts_at')
            ->get();

        return view('tickets.index', compact('tickets'));
    }

    public function generateCaptcha(Ticket $ticket)
    {
        abort_unless($ticket->isOnSale(), 403, 'Penjualan tiket ini belum dibuka atau sudah berakhir.');
        abort_unless($ticket->stock > 0, 410, 'Slot sudah habis.');
        abort_unless(extension_loaded('gd'), 500, 'Ekstensi PHP GD harus diaktifkan untuk membuat CAPTCHA.');

        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $randomText = app()->runningUnitTests() ? self::TEST_CAPTCHA_TEXT : '';

        if (! app()->runningUnitTests()) {
            for ($index = 0; $index < 6; $index++) {
                $randomText .= $characters[random_int(0, strlen($characters) - 1)];
            }
        }

        session([
            'captcha_text' => $randomText,
            'captcha_user_id' => auth()->id(),
            'captcha_ticket_id' => $ticket->id,
        ]);

        $width = 300;
        $height = 88;
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            abort(500, 'Gagal membuat gambar CAPTCHA.');
        }

        try {
            for ($y = 0; $y < $height; $y++) {
                $shade = (int) (247 - ($y / $height * 8));
                $background = imagecolorallocate($image, $shade, $shade + 2, 250);
                imageline($image, 0, $y, $width, $y, $background);
            }

            for ($index = 0; $index < 7; $index++) {
                $lineColor = imagecolorallocate(
                    $image,
                    random_int(190, 220),
                    random_int(195, 225),
                    random_int(210, 235),
                );

                $amplitude = random_int(4, 10);
                $frequency = random_int(35, 65);
                $phase = random_int(0, 628) / 100;
                $previousY = (int) ($height / 2 + sin($phase) * $amplitude);

                for ($x = 1; $x < $width; $x++) {
                    $waveY = (int) ($height / 2 + sin(($x / $frequency) + $phase) * $amplitude);
                    imageline($image, $x - 1, $previousY, $x, $waveY, $lineColor);
                    $previousY = $waveY;
                }
            }

            for ($index = 0; $index < 110; $index++) {
                $dotColor = imagecolorallocate(
                    $image,
                    random_int(175, 215),
                    random_int(180, 220),
                    random_int(190, 230),
                );

                imagesetpixel($image, random_int(0, $width - 1), random_int(0, $height - 1), $dotColor);
            }

            foreach (str_split($randomText) as $index => $character) {
                $textColor = [
                    random_int(18, 45),
                    random_int(35, 80),
                    random_int(48, 95),
                ];

                $this->drawCaptchaCharacter(
                    $image,
                    $character,
                    18 + ($index * 46) + random_int(-3, 3),
                    random_int(20, 32),
                    random_int(-18, 18),
                    $textColor,
                );
            }

            for ($index = 0; $index < 3; $index++) {
                $lineColor = imagecolorallocate($image, 165, 184, 199);
                imageline(
                    $image,
                    random_int(0, 40),
                    random_int(16, 72),
                    random_int(250, $width - 1),
                    random_int(16, 72),
                    $lineColor,
                );
            }

            ob_start();
            $encoded = imagepng($image);
            $png = ob_get_clean();

            if (! $encoded || $png === false) {
                abort(500, 'Gagal menghasilkan gambar CAPTCHA.');
            }
        } finally {
            imagedestroy($image);
        }

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Draw one independently rotated character using GD's bundled bitmap font.
     *
     * @param  array{0: int, 1: int, 2: int}  $textColor
     */
    private function drawCaptchaCharacter(
        \GdImage $image,
        string $character,
        int $x,
        int $y,
        int $angle,
        array $textColor,
    ): void {
        $source = imagecreatetruecolor(11, 17);
        $glyph = imagecreatetruecolor(32, 48);

        if ($source === false || $glyph === false) {
            if ($source !== false) {
                imagedestroy($source);
            }

            if ($glyph !== false) {
                imagedestroy($glyph);
            }

            abort(500, 'Gagal menggambar karakter CAPTCHA.');
        }

        $transparent = imagecolorallocatealpha($source, 255, 255, 255, 127);
        imagealphablending($source, false);
        imagefilledrectangle($source, 0, 0, 10, 16, $transparent);
        imagesavealpha($source, true);
        imagealphablending($source, true);

        $ink = imagecolorallocate($source, $textColor[0], $textColor[1], $textColor[2]);

        if (! imagestring($source, 5, 0, 1, $character, $ink)) {
            imagedestroy($source);
            imagedestroy($glyph);

            abort(500, 'Gagal menggambar karakter CAPTCHA.');
        }

        $glyphTransparent = imagecolorallocatealpha($glyph, 255, 255, 255, 127);
        imagealphablending($glyph, false);
        imagefilledrectangle($glyph, 0, 0, 31, 47, $glyphTransparent);
        imagesavealpha($glyph, true);

        if (! imagecopyresized($glyph, $source, 4, 3, 0, 0, 24, 40, 11, 17)) {
            imagedestroy($source);
            imagedestroy($glyph);

            abort(500, 'Gagal menggambar karakter CAPTCHA.');
        }

        imagealphablending($glyph, true);
        $rotated = imagerotate($glyph, $angle, $glyphTransparent);
        imagedestroy($source);
        imagedestroy($glyph);

        if ($rotated === false) {
            abort(500, 'Gagal memutar karakter CAPTCHA.');
        }

        imagealphablending($image, true);
        $copied = imagecopy($image, $rotated, $x, $y, 0, 0, imagesx($rotated), imagesy($rotated));
        imagedestroy($rotated);

        if (! $copied) {
            abort(500, 'Gagal menempelkan karakter CAPTCHA.');
        }
    }

    public function stock(Ticket $ticket): JsonResponse
    {
        abort_unless($ticket->is_published, 404);
        $ticket->refresh();

        return response()->json([
            'stock' => $ticket->stock,
            'available' => $ticket->saleStatus() === 'open',
            'status' => $ticket->saleStatus(),
            'starts_at' => $ticket->sales_starts_at?->timestamp,
            'ends_at' => $ticket->sales_ends_at?->timestamp,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function registrations(Ticket $ticket): JsonResponse
    {
        abort_unless($ticket->is_published, 404);
        abort_unless(in_array($ticket->saleStatus(), ['ended', 'sold_out'], true), 403);

        $paidCount = (int) Order::query()
            ->where('ticket_id', $ticket->id)
            ->where('status', 'paid')
            ->sum('quantity');
        $pendingCount = (int) Order::query()
            ->where('ticket_id', $ticket->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->sum('quantity');

        return response()->json([
            'registered_count' => $paidCount + $pendingCount,
            'pending_payment_count' => $pendingCount,
            'paid_count' => $paidCount,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function buy(Request $request, Ticket $ticket): RedirectResponse
    {
        if (! $ticket->isOnSale()) {
            return back()->with('error', 'Penjualan tiket ini belum dibuka atau sudah berakhir.');
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:'.$ticket->stock],
            'captcha' => ['required', 'string', 'size:6'],
        ]);

        $providedCaptcha = strtoupper(trim($validated['captcha']));
        $sessionCaptcha = strtoupper((string) session('captcha_text', ''));
        $captchaUserId = session('captcha_user_id');
        $captchaTicketId = session('captcha_ticket_id');

        session()->forget(['captcha_text', 'captcha_user_id', 'captcha_ticket_id']);

        if (
            $sessionCaptcha === ''
            || (int) $captchaUserId !== (int) $request->user()->id
            || (int) $captchaTicketId !== (int) $ticket->id
            || ! hash_equals($sessionCaptcha, $providedCaptcha)
        ) {
            return back()
                ->withErrors(['captcha' => 'CAPTCHA salah atau sudah kedaluwarsa. Silakan gunakan gambar baru.'])
                ->withInput($request->except('captcha'));
        }

        $order = DB::transaction(function () use ($request, $ticket, $validated): ?Order {
            $lockedTicket = Ticket::query()
                ->lockForUpdate()
                ->find($ticket->id);

            if (
                $lockedTicket === null
                || ! $lockedTicket->isOnSale()
                || $lockedTicket->stock < $validated['quantity']
            ) {
                return null;
            }

            $unitPrice = $lockedTicket->price;
            $quantity = (int) $validated['quantity'];
            $lockedTicket->decrement('stock', $quantity);

            return $request->user()->orders()->create([
                'ticket_id' => $lockedTicket->id,
                'ticket_name' => $lockedTicket->name,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'total' => $unitPrice * $quantity,
                'status' => 'pending',
                'public_id' => (string) Str::uuid(),
                'expires_at' => now()->addDay(),
            ]);
        }, attempts: 3);

        if ($order === null) {
            return back()
                ->with('error', 'Stok berubah atau penjualan sudah berakhir. Perbarui halaman dan coba lagi.')
                ->withInput($request->except('captcha'));
        }

        return redirect()->route('orders.payment', $order);
    }

    public function payment(Request $request, int $order): View
    {
        $order = $request->user()->orders()->findOrFail($order);

        if (
            $order->status === 'pending'
            && ($order->expires_at === null || ! $order->expires_at->isFuture())
        ) {
            app(OrderReservationService::class)->expire($order);
            $order->refresh();
        }

        $walletBalance = $request->user()->wallet()->value('balance') ?? 0;

        return view('tickets.payment', compact('order', 'walletBalance'));
    }

    public function payWithWallet(
        Request $request,
        int $order,
        OrderReservationService $reservations,
    ): RedirectResponse {
        $result = DB::transaction(function () use ($request, $order, $reservations): string {
            $lockedOrder = $request->user()->orders()
                ->lockForUpdate()
                ->findOrFail($order);

            if ($lockedOrder->status !== 'pending') {
                return $lockedOrder->status === 'paid' ? 'already_paid' : $lockedOrder->status;
            }

            if ($lockedOrder->expires_at === null || ! $lockedOrder->expires_at->isFuture()) {
                $reservations->expireLocked($lockedOrder);

                return 'expired';
            }

            if ($lockedOrder->gateway_session_requested_at !== null) {
                return 'gateway_pending';
            }

            $wallet = $request->user()->wallet()->lockForUpdate()->firstOrFail();

            if ($wallet->balance < $lockedOrder->total) {
                return 'insufficient_funds';
            }

            $wallet->decrement('balance', $lockedOrder->total);
            $wallet->refresh();
            $wallet->transactions()->create([
                'order_id' => $lockedOrder->id,
                'type' => 'order_payment',
                'amount' => $lockedOrder->total,
                'balance_after' => $wallet->balance,
                'description' => 'Pembayaran pesanan '.$lockedOrder->public_id,
            ]);

            $lockedOrder->forceFill([
                'status' => 'paid',
                'payment_method' => 'wallet',
                'paid_at' => now(),
            ])->save();

            return 'paid';
        }, attempts: 3);

        if ($result === 'already_paid') {
            return redirect()->route('orders.payment', $order)
                ->with('success', 'Pesanan ini sudah berstatus lunas.');
        }

        if ($result !== 'paid') {
            $messages = [
                'insufficient_funds' => 'Saldo akun tidak mencukupi. Silakan isi saldo terlebih dahulu.',
                'expired' => 'Batas pembayaran telah berakhir. Slot telah dilepas kembali.',
                'gateway_pending' => 'Sesi Midtrans sudah diminta. Selesaikan pembayaran di gateway atau hubungi admin sebelum mencoba metode lain.',
                'pending' => 'Pesanan belum berhasil dibayar. Silakan coba lagi.',
            ];

            return back()->with('error', $messages[$result] ?? 'Pesanan tidak dapat dibayar.');
        }

        return redirect()
            ->route('orders.payment', $order)
            ->with('success', 'Pembayaran menggunakan saldo akun berhasil.');
    }

    public function payWithMidtrans(
        Request $request,
        int $order,
        MidtransService $midtrans,
        OrderReservationService $reservations,
    ): RedirectResponse {
        if ((string) config('services.midtrans.server_key') === '') {
            return back()->with('error', 'Midtrans belum dikonfigurasi. Tambahkan MIDTRANS_SERVER_KEY di .env.');
        }

        $preparation = DB::transaction(function () use ($request, $order, $reservations): array {
            $lockedOrder = $request->user()->orders()
                ->lockForUpdate()
                ->findOrFail($order);

            if ($lockedOrder->status !== 'pending') {
                return ['state' => $lockedOrder->status, 'url' => null];
            }

            if ($lockedOrder->expires_at === null || ! $lockedOrder->expires_at->isFuture()) {
                $reservations->expireLocked($lockedOrder);

                return ['state' => 'expired', 'url' => null];
            }

            if ($lockedOrder->gateway_payment_url !== null) {
                return ['state' => 'redirect', 'url' => $lockedOrder->gateway_payment_url];
            }

            if ($lockedOrder->gateway_session_requested_at !== null) {
                return ['state' => 'in_progress', 'url' => null];
            }

            $lockedOrder->forceFill([
                'payment_method' => 'midtrans',
                'gateway_session_requested_at' => now(),
            ])->save();

            return ['state' => 'create', 'url' => null];
        }, attempts: 3);

        if ($preparation['state'] === 'redirect') {
            return redirect()->away($preparation['url']);
        }

        if ($preparation['state'] !== 'create') {
            $message = $preparation['state'] === 'expired'
                ? 'Batas pembayaran telah berakhir. Slot telah dilepas kembali.'
                : ($preparation['state'] === 'in_progress'
                    ? 'Sesi gateway sudah diminta. Jangan meminta sesi baru atau beralih ke saldo sebelum statusnya dikonfirmasi; muat ulang halaman atau hubungi admin.'
                    : 'Pesanan ini tidak lagi menunggu pembayaran.');

            return redirect()->route('orders.payment', $order)->with('error', $message);
        }

        $lockedOrder = $request->user()->orders()->findOrFail($order);

        try {
            $session = $midtrans->createSnapSession($lockedOrder);
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            Log::error('Midtrans Snap session could not be created.', [
                'order_id' => $lockedOrder->public_id,
                'exception' => $exception->getMessage(),
            ]);

            return back()->with('error', 'Sesi gateway gagal dibuat dan tidak akan diminta ulang untuk menghindari transaksi ganda. Pesanan tetap dicadangkan; hubungi admin untuk pemeriksaan.');
        }

        $saved = DB::transaction(function () use ($request, $lockedOrder, $session): bool {
            $currentOrder = $request->user()->orders()
                ->lockForUpdate()
                ->findOrFail($lockedOrder->id);

            if (
                $currentOrder->status !== 'pending'
                || $currentOrder->expires_at === null
                || ! $currentOrder->expires_at->isFuture()
            ) {
                return false;
            }

            $currentOrder->forceFill([
                'payment_method' => 'midtrans',
                'gateway_payment_url' => $session['redirect_url'],
            ])->save();

            return true;
        }, attempts: 3);

        if (! $saved) {
            return redirect()->route('orders.payment', $lockedOrder)
                ->with('error', 'Pesanan telah kedaluwarsa saat sesi pembayaran dibuat. Jangan lanjutkan pembayaran; hubungi admin jika halaman gateway terbuka.');
        }

        return redirect()->away($session['redirect_url']);
    }
}
