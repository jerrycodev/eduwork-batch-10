<?php

use App\Mail\OrderTransactionalMail;
use App\Models\EmailDeliveryLog;
use App\Models\Order;
use App\Models\User;
use App\Services\EmailDeliveryTracker;
use App\Services\OrderEmailService;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\SentMessage;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Email;

function emailDeliveryTestOrder(?User $user = null): Order
{
    $user ??= User::factory()->create();

    return Order::query()->create([
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'total' => 125000,
        'status' => 'pending',
        'payment_status' => 'pending',
    ]);
}

test('transactional email queueing creates a delivery log linked to its order and recipient', function () {
    Mail::fake();
    $order = emailDeliveryTestOrder();

    app(OrderEmailService::class)->send($order, 'order_created');

    $log = EmailDeliveryLog::query()->sole();

    expect($log->order_id)->toBe($order->id)
        ->and($log->recipient)->toBe($order->user_email)
        ->and($log->type)->toBe('order_created')
        ->and($log->status)->toBe('queued')
        ->and($log->queued_at)->not->toBeNull();

    Mail::assertQueued(OrderTransactionalMail::class, fn (OrderTransactionalMail $mail): bool => $mail->deliveryLogId === $log->id);
});

test('mail transport events record attempts and mark delivery as sent', function () {
    $order = emailDeliveryTestOrder();
    $log = EmailDeliveryLog::query()->create([
        'order_id' => $order->id,
        'recipient' => $order->user_email,
        'type' => 'order_created',
        'status' => 'queued',
        'queued_at' => now(),
    ]);
    $email = (new Email)->from('sender@example.test')->to('recipient@example.test')->text('Delivery log test');
    $email->getHeaders()->addTextHeader('X-Email-Delivery-Log-ID', $log->id);
    $tracker = app(EmailDeliveryTracker::class);

    $tracker->sending(new MessageSending($email));
    expect($log->fresh()->attempt_count)->toBe(1);

    $sent = new SentMessage(new SymfonySentMessage($email, Envelope::create($email)));
    $tracker->sent(new MessageSent($sent));
    expect($log->fresh()->status)->toBe('sent')
        ->and($log->fresh()->sent_at)->not->toBeNull()
        ->and($log->fresh()->last_error)->toBeNull();
});

test('failed queued email records error and admin can retry it', function () {
    Mail::fake();
    $admin = User::factory()->create(['role' => 'admin']);
    $order = emailDeliveryTestOrder();
    $log = EmailDeliveryLog::query()->create([
        'order_id' => $order->id,
        'recipient' => $order->user_email,
        'type' => 'payment_successful',
        'status' => 'queued',
        'queued_at' => now(),
    ]);

    (new OrderTransactionalMail($order, 'payment_successful', $log->id))->failed(new RuntimeException('SMTP connection refused'));
    expect($log->fresh()->status)->toBe('failed')
        ->and($log->fresh()->last_error)->toBe('SMTP connection refused');

    $this->actingAs($admin)
        ->post(route('email-delivery-logs.retry', $log))
        ->assertRedirect()
        ->assertSessionHas('status');

    expect($log->fresh()->status)->toBe('queued')
        ->and($log->fresh()->retry_count)->toBe(1)
        ->and($log->fresh()->last_error)->toBeNull();

    Mail::assertQueued(OrderTransactionalMail::class, fn (OrderTransactionalMail $mail): bool => $mail->deliveryLogId === $log->id);
});

test('email delivery log page is admin-only and can filter by status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $order = emailDeliveryTestOrder();
    EmailDeliveryLog::query()->create([
        'order_id' => $order->id,
        'recipient' => $order->user_email,
        'type' => 'order_created',
        'status' => 'failed',
        'last_error' => 'SMTP unavailable',
        'queued_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('email-delivery-logs.index', ['status' => 'failed']))
        ->assertOk()
        ->assertSee('Email Delivery Logs')
        ->assertSee($order->user_email)
        ->assertSee('SMTP unavailable');

    $this->actingAs(User::factory()->create(['role' => 'customer']))
        ->get(route('email-delivery-logs.index'))
        ->assertRedirect(route('home'));
});
