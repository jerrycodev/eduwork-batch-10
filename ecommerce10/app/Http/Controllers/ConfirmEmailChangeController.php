<?php

namespace App\Http\Controllers;

use App\Models\EmailDeliveryLog;
use App\Models\User;
use App\Notifications\EmailAddressChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Services\EmailDeliveryTracker;
use Throwable;

class ConfirmEmailChangeController extends Controller
{
    public function __invoke(Request $request, string $user, string $hash): RedirectResponse
    {
        $account = User::query()->whereKey($user)->whereNotNull('pending_email')->firstOrFail();
        $pendingEmail = $account->pending_email;

        abort_unless(hash_equals(hash('sha256', $pendingEmail), $hash), 403);

        $oldEmail = $account->email;
        $account->forceFill([
            'email' => $pendingEmail,
            'pending_email' => null,
            'email_verified_at' => now(),
        ])->save();

        $deliveryLog = EmailDeliveryLog::query()->create([
            'recipient' => $oldEmail,
            'type' => 'email_changed_notification',
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        try {
            Notification::route('mail', $oldEmail)->notify(new EmailAddressChanged($pendingEmail, $deliveryLog->id, $account->name));
        } catch (Throwable $exception) {
            app(EmailDeliveryTracker::class)->failed($deliveryLog->id, $exception);
            report($exception);
        }

        return redirect()->route('login')->with('status', 'email-change-confirmed');
    }
}
