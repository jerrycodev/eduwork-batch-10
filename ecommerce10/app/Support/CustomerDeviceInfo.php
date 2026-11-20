<?php

namespace App\Support;

use Illuminate\Http\Request;

class CustomerDeviceInfo
{
    public static function fromRequest(Request $request): array
    {
        $agent = (string) $request->userAgent();
        $browser = match (true) {
            str_contains($agent, 'Edg/') => 'Microsoft Edge',
            str_contains($agent, 'OPR/') => 'Opera',
            str_contains($agent, 'Chrome/') => 'Google Chrome',
            str_contains($agent, 'Firefox/') => 'Mozilla Firefox',
            str_contains($agent, 'Safari/') && ! str_contains($agent, 'Chrome/') => 'Safari',
            default => 'Unknown',
        };
        $platform = match (true) {
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'Mac OS') || str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') || str_contains($agent, 'iOS') => 'iOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => 'Unknown',
        };
        $device = match (true) {
            str_contains($agent, 'iPad') => 'Tablet',
            str_contains($agent, 'Mobile') || str_contains($agent, 'iPhone') || str_contains($agent, 'Android') => 'Mobile',
            default => 'Desktop',
        };

        return ['device_type' => $device, 'platform' => $platform, 'browser' => $browser];
    }
}
