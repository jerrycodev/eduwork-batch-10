<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\EmailDeliveryLog;
use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Throwable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Menyiapkan halaman perubahan data yang sudah ada.
     */
    public function edit(Request $request): View|RedirectResponse
    {
        if ($request->user()->role === 'admin') {
            return redirect()->route('admin.profile.edit');
        }

        return view('profile.customer.edit', [
            'user' => $request->user(),
            'profileUpdateRoute' => 'profile.update',
            'profileDestroyRoute' => 'profile.destroy',
        ]);
    }

    /**
     * Mengirim ulang tautan konfirmasi untuk alamat email customer yang masih tertunda.
     */
    public function resendEmailChangeConfirmation(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'customer' && filled($user->pending_email), 404);

        $confirmationUrl = URL::temporarySignedRoute(
            'profile.email-change.confirm',
            now()->addMinutes(60),
            ['user' => $user->id, 'hash' => hash('sha256', $user->pending_email)],
        );

        $deliveryLog = EmailDeliveryLog::query()->create([
            'recipient' => $user->pending_email,
            'type' => 'email_change_confirmation',
            'status' => 'queued',
            'queued_at' => now(),
        ]);

        try {
            Notification::route('mail', $user->pending_email)
                ->notify(new ConfirmEmailChange($confirmationUrl, $deliveryLog->id, $user->name));
        } catch (Throwable $exception) {
            app(\App\Services\EmailDeliveryTracker::class)->failed($deliveryLog->id, $exception);
            report($exception);

            return Redirect::route('profile.edit')->with('status', 'email-change-resend-failed');
        }

        return Redirect::route('profile.edit')->with('status', 'email-change-resent');
    }

    public function adminEdit(Request $request): View
    {
        return view('profile.admin.edit', [
            'user' => $request->user(),
            'profileUpdateRoute' => 'admin.profile.update',
            'profileDestroyRoute' => 'admin.profile.destroy',
        ]);
    }

    /**
     * Memvalidasi dan menyimpan perubahan data dengan tetap menjaga aturan bisnis.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $requestedEmail = $data['email'];
        unset($data['email'], $data['password']);

        $user->fill($data);

        if ($user->role === 'customer' && $requestedEmail !== $user->email) {
            $user->pending_email = $requestedEmail;
            $user->save();

            $confirmationUrl = URL::temporarySignedRoute(
                'profile.email-change.confirm',
                now()->addMinutes(60),
                ['user' => $user->id, 'hash' => hash('sha256', $requestedEmail)],
            );

            $deliveryLog = EmailDeliveryLog::query()->create([
                'recipient' => $requestedEmail,
                'type' => 'email_change_confirmation',
                'status' => 'queued',
                'queued_at' => now(),
            ]);

            try {
                Notification::route('mail', $requestedEmail)->notify(new ConfirmEmailChange($confirmationUrl, $deliveryLog->id, $user->name));
            } catch (Throwable $exception) {
                app(\App\Services\EmailDeliveryTracker::class)->failed($deliveryLog->id, $exception);
                report($exception);
            }

            return Redirect::route('profile.edit')->with('status', 'email-change-pending');
        }

        if ($user->role === 'customer' && $requestedEmail === $user->email && $user->pending_email) {
            $user->pending_email = null;
        }

        if ($user->role === 'admin' && $requestedEmail !== $user->email) {
            $user->email = $requestedEmail;
            $user->email_verified_at = null;
        }

        $user->save();

        $route = $user->role === 'admin' ? 'admin.profile.edit' : 'profile.edit';

        return Redirect::route($route)->with('status', 'profile-updated');
    }

    /**
     * Memeriksa ketentuan penghapusan lalu menghapus data yang diizinkan.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($user->role === 'admin' && User::query()->where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'Administrator terakhir tidak dapat menghapus akunnya. Tambahkan administrator lain terlebih dahulu.');
        }

        Auth::guard($user->role === 'admin' ? 'admin' : 'customer')->logout();
        $user->delete();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
