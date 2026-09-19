<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Services\GeoIpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackController extends Controller
{
    /**
     * Enregistre une visite / page vue (endpoint public appelé par le frontend).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_id' => ['required', 'string', 'max:64'],
            'session_id' => ['nullable', 'string', 'max:64'],
            'path' => ['nullable', 'string', 'max:255'],
            'referrer' => ['nullable', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'in:web,pwa'],
        ]);

        $ip = $this->resolveClientIp($request);
        $geo = GeoIpService::resolve($ip);

        // Utilisateur authentifié si un token JWT valide est fourni (route publique).
        $userId = null;
        try {
            if ($request->bearerToken()) {
                $userId = optional(auth('api')->user())->id;
            }
        } catch (\Throwable $e) {
            $userId = null;
        }

        Visit::create([
            'visitor_id' => $validated['visitor_id'],
            'user_id' => $userId,
            'session_id' => $validated['session_id'] ?? null,
            'ip_address' => $ip,
            'country' => $geo['country'],
            'country_code' => $geo['country_code'],
            'city' => $geo['city'],
            'platform' => $validated['platform'] ?? 'web',
            'device_type' => $this->detectDeviceType($request->userAgent()),
            'path' => $validated['path'] ?? null,
            'referrer' => $validated['referrer'] ?? null,
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json(['status' => 'success'], 201);
    }

    /**
     * Récupère l'IP réelle du client en tenant compte des proxies courants.
     */
    protected function resolveClientIp(Request $request): ?string
    {
        $cfIp = $request->header('CF-Connecting-IP');
        if ($cfIp) {
            return $cfIp;
        }

        $forwarded = $request->header('X-Forwarded-For');
        if ($forwarded) {
            return trim(explode(',', $forwarded)[0]);
        }

        return $request->ip();
    }

    /**
     * Déduit le type d'appareil à partir du User-Agent.
     */
    protected function detectDeviceType(?string $userAgent): ?string
    {
        if (! $userAgent) {
            return null;
        }

        $ua = strtolower($userAgent);

        if (str_contains($ua, 'ipad') || str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        if (str_contains($ua, 'mobi') || str_contains($ua, 'android') || str_contains($ua, 'iphone')) {
            return 'mobile';
        }

        return 'desktop';
    }
}
