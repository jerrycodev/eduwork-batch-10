<?php

namespace App\Http\Middleware;

use App\Models\CustomerActivityLog;
use App\Support\CustomerDeviceInfo;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogCustomerActivity
{
    /**
     * Mencatat perubahan yang dilakukan customer tanpa menyimpan payload request
     * maupun data sensitif seperti password, token, dan informasi pembayaran.
     *
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // Lewati request Precognition karena request ini hanya untuk validasi
        // form dan bukan aksi customer yang benar-benar dijalankan.
        if ($request->isPrecognitive()) {
            return $next($request);
        }

        // Ambil customer yang sedang login sebelum request diteruskan.
        // Data user ini digunakan sebagai identitas actor pada activity log.
        $user = $request->user('customer');

        // Jalankan controller/middleware berikutnya terlebih dahulu.
        // Dengan begitu, status response dan atribut yang disiapkan controller
        // (misalnya subject atau metadata aktivitas) sudah tersedia saat dicatat.
        $response = $next($request);

        // Batasi pencatatan hanya untuk HTTP method yang berpotensi mengubah data.
        // GET, HEAD, dan OPTIONS tidak dimasukkan ke riwayat aktivitas.
        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        // Pastikan actor adalah customer yang valid, bukan guest atau admin.
        if (! $user || $user->role !== 'customer') {
            return $response;
        }

        // Ambil informasi route untuk menentukan nama aksi dan subject.
        // Route name lebih stabil dan mudah dibaca daripada URL mentah.
        $route = $request->route();
        $routeName = $route instanceof Route ? $route->getName() : null;

        // Abaikan seluruh aktivitas keranjang belanja sesuai keputusan proyek:
        // perubahan cart tidak perlu ditampilkan pada customer activity history.
        if (is_string($routeName) && str_starts_with($routeName, 'cart.')) {
            return $response;
        }

        // Tentukan subject yang terkait dengan aktivitas.
        // Controller dapat menetapkan subject secara eksplisit melalui request
        // attribute; jika tidak ada, subject dicari dari parameter route model.
        $subject = $request->attributes->get('customer_activity_subject') ?? $this->resolveSubject($route);

        // Susun metadata umum aktivitas, tanpa memasukkan seluruh input request.
        // Metadata berisi path, status response, info perangkat, dan metadata
        // tambahan yang secara eksplisit disediakan oleh controller.
        $metadata = array_merge(
            ['path' => '/'.ltrim($request->path(), '/'), 'response_status' => $response->getStatusCode()],
            CustomerDeviceInfo::fromRequest($request),
            (array) $request->attributes->get('customer_activity_metadata', []),
        );

        // Simpan log secara terpisah dari proses utama.
        // Kegagalan pencatatan tidak boleh menggagalkan aksi customer yang
        // sebenarnya sudah diproses oleh controller.
        try {
            // Simpan aktivitas utama beserta identitas actor, subject, route,
            // HTTP method, status response, IP, user-agent, dan metadata.
            CustomerActivityLog::query()->create([
                'user_id' => $user->id,
                'actor_name' => $user->name,
                'actor_email' => $user->email,
                'action' => $this->actionLabel($routeName, $request),
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject && method_exists($subject, 'getKey') ? (string) $subject->getKey() : null,
                'route_name' => $routeName,
                'method' => $request->method(),
                'response_status' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000) ?: null,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);

            // Beberapa proses dapat menghasilkan aktivitas turunan.
            // Jika controller menetapkan secondary action, simpan sebagai
            // baris log kedua dengan subject dan konteks request yang sama.
            $secondaryAction = $request->attributes->get('customer_activity_secondary_action');

            if (is_string($secondaryAction) && $secondaryAction !== '') {
                CustomerActivityLog::query()->create([
                    'user_id' => $user->id,
                    'actor_name' => $user->name,
                    'actor_email' => $user->email,
                    'action' => $secondaryAction,
                    'subject_type' => $subject ? $subject::class : null,
                    'subject_id' => $subject && method_exists($subject, 'getKey') ? (string) $subject->getKey() : null,
                    'route_name' => $routeName,
                    'method' => $request->method(),
                    'response_status' => $response->getStatusCode(),
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 2000) ?: null,
                    'metadata' => array_merge($metadata, ['order_number' => data_get($metadata, 'order_number')]),
                    'created_at' => now(),
                ]);
            }
        } catch (Throwable $exception) {
            // Catat error ke sistem logging Laravel untuk investigasi,
            // tetapi jangan ubah response yang diterima customer.
            report($exception);
        }

        // Kembalikan response asli dari proses controller.
        return $response;
    }

    /**
     * Mengubah nama route menjadi kode aksi yang konsisten untuk activity log.
     */
    private function actionLabel(?string $routeName, Request $request): string
    {
        // Gunakan label eksplisit untuk aksi penting agar mudah difilter
        // dan ditampilkan dengan bahasa/label yang konsisten di halaman admin.
        return match ($routeName) {
            'checkout.store' => 'order.created',
            // 'checkout.shipping-rates' => 'checkout.shipping_rates_requested',
            'orders.payment.create' => 'payment.initiated',
            'orders.cancel' => 'order.cancellation_requested',
            'orders.confirm-received' => 'order.confirmed_received',
            'orders.returns.store' => 'order.return_requested',
            'orders.returns.revisions.submit' => 'order.return_revision_submitted',
            'orders.returns.shipment' => 'order.return_shipment_submitted',
            'profile.update' => 'profile.updated',
            'profile.destroy' => 'account.deletion_requested',
            'addresses.store' => 'address.created',
            'addresses.update' => 'address.updated',
            'addresses.destroy' => 'address.deleted',
            'addresses.default' => 'address.default_changed',
            // 'wishlist.toggle' => 'wishlist.toggled',
            // 'wishlist.destroy' => 'wishlist.removed',
            // 'notifications.read' => 'notification.read',
            // 'notifications.read-all' => 'notifications.read_all',
            'verification.send' => 'email.verification_resent',

            // Untuk route yang belum memiliki mapping khusus, gunakan nama
            // route; jika route name tidak tersedia, gunakan method + path.
            default => $routeName ?: strtolower($request->method().' '.$request->path()),
        };
    }

    /**
     * Mencari model yang menjadi subject aktivitas dari parameter route.
     */
    private function resolveSubject(mixed $route): mixed
    {
        // Jika request tidak terhubung dengan objek Route, subject tidak diketahui.
        if (! $route instanceof Route) {
            return null;
        }

        // Periksa parameter route satu per satu dan gunakan parameter pertama
        // yang berupa model/object dengan getKey() (misalnya Order atau Address).
        foreach ($route->parameters() as $parameter) {
            if (is_object($parameter) && method_exists($parameter, 'getKey')) {
                return $parameter;
            }
        }

        // Tidak ada model pada parameter route, sehingga log disimpan
        // tanpa subject_type dan subject_id.
        return null;
    }
}
