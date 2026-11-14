<?php

namespace App\Http\Middleware;

use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogAdminActivity
{
    /**
     * Record successful and rejected admin mutation attempts without storing request payloads.
     *
     * @param  \Closure(Request): Response  $next
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return $response;
        }

        $user = $request->user();
        if (! $user || $user->role !== 'admin') {
            return $response;
        }

        $route = $request->route();
        $subject = $this->resolveSubject($route);
        $routeName = $route instanceof Route ? $route->getName() : null;

        try {
            AdminActivityLog::query()->create([
                'user_id' => $user->id,
                'actor_name' => $user->name,
                'actor_email' => $user->email,
                'action' => $routeName ?: $request->method().' '.$request->path(),
                'subject_type' => $subject ? $subject::class : null,
                'subject_id' => $subject && method_exists($subject, 'getKey') ? (string) $subject->getKey() : null,
                'route_name' => $routeName,
                'method' => $request->method(),
                'response_status' => $response->getStatusCode(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 2000) ?: null,
                'metadata' => [
                    'path' => '/'.ltrim($request->path(), '/'),
                    'response_status' => $response->getStatusCode(),
                ],
                'created_at' => now(),
            ]);
        } catch (Throwable $exception) {
            // Audit persistence must never turn a completed admin action into a failed request.
            report($exception);
        }

        return $response;
    }

    private function resolveSubject(mixed $route): mixed
    {
        if (! $route instanceof Route) {
            return null;
        }

        foreach ($route->parameters() as $parameter) {
            if (is_object($parameter) && method_exists($parameter, 'getKey')) {
                return $parameter;
            }
        }

        return null;
    }
}
