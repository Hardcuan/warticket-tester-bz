<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\WarTicketAnnounced;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(): View
    {
        $tickets = Ticket::query()
            ->withCount('orders')
            ->latest()
            ->paginate(15);

        return view('admin.tickets.index', compact('tickets'));
    }

    public function create(): View
    {
        return view('admin.tickets.form', ['ticket' => new Ticket]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedTicket($request);
        $ticket = Ticket::query()->create($this->ticketAttributes($validated));

        if ($ticket->is_published) {
            $this->notifySubscribers($ticket, $request->user());
        }

        return redirect()
            ->route('admin.tickets.index')
            ->with('success', 'Event war tiket berhasil dibuat.');
    }

    public function edit(Ticket $ticket): View
    {
        return view('admin.tickets.form', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket): RedirectResponse
    {
        $validated = $this->validatedTicket($request);
        $wasPublished = $ticket->is_published;
        $ticket->fill($this->ticketAttributes($validated));
        $announcementChanged = $ticket->isDirty([
            'name',
            'description',
            'price',
            'sales_starts_at',
            'sales_ends_at',
        ]);
        $ticket->save();

        if ($ticket->is_published && (! $wasPublished || $announcementChanged)) {
            $this->notifySubscribers($ticket, $request->user());
        }

        return redirect()
            ->route('admin.tickets.index')
            ->with('success', 'Event war tiket berhasil diperbarui.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        if ($ticket->orders()->exists()) {
            throw ValidationException::withMessages([
                'ticket' => 'Event ini tidak bisa dihapus karena sudah memiliki transaksi.',
            ]);
        }

        $ticket->delete();

        return redirect()
            ->route('admin.tickets.index')
            ->with('success', 'Event war tiket berhasil dihapus.');
    }

    private function validatedTicket(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'stock' => ['required', 'integer', 'min:0', 'max:1000000'],
            'sales_starts_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'sales_ends_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:sales_starts_at'],
            'is_published' => ['nullable', 'boolean'],
        ]);
    }

    private function ticketAttributes(array $validated): array
    {
        return [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'sales_starts_at' => Carbon::createFromFormat(
                'Y-m-d\TH:i',
                $validated['sales_starts_at'],
                config('app.timezone'),
            ),
            'sales_ends_at' => Carbon::createFromFormat(
                'Y-m-d\TH:i',
                $validated['sales_ends_at'],
                config('app.timezone'),
            ),
            'is_published' => (bool) ($validated['is_published'] ?? false),
        ];
    }

    private function notifySubscribers(Ticket $ticket, User $admin): void
    {
        User::query()
            ->where('email_notifications', true)
            ->where('id', '!=', $admin->id)
            ->chunkById(100, function ($users) use ($ticket): void {
                Notification::send($users, new WarTicketAnnounced($ticket));
            });
    }
}
