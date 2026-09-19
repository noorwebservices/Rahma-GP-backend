<?php

namespace App\Services;

use App\Models\IpGeolocation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoIpService
{
    /**
     * Résout le pays / la ville d'une adresse IP, avec mise en cache en base
     * pour éviter d'appeler l'API externe (ip-api.com) à répétition.
     *
     * @return array{country: ?string, country_code: ?string, city: ?string}
     */
    public static function resolve(?string $ip): array
    {
        $empty = ['country' => null, 'country_code' => null, 'city' => null];

        if (empty($ip) || self::isPrivateIp($ip)) {
            return $empty;
        }

        // 1) Cache local
        $cached = IpGeolocation::find($ip);
        if ($cached) {
            return [
                'country' => $cached->country,
                'country_code' => $cached->country_code,
                'city' => $cached->city,
            ];
        }

        // 2) Appel API externe (gratuite, limitée) avec timeout court
        try {
            $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
                'fields' => 'status,country,countryCode,city',
            ]);

            if ($response->ok() && ($response->json('status') === 'success')) {
                $data = [
                    'country' => $response->json('country'),
                    'country_code' => $response->json('countryCode'),
                    'city' => $response->json('city'),
                ];

                IpGeolocation::updateOrCreate(['ip_address' => $ip], $data);

                return $data;
            }
        } catch (\Throwable $e) {
            Log::warning('GeoIP resolution failed for '.$ip.': '.$e->getMessage());
        }

        // 3) Échec : on met en cache un résultat vide pour ne pas rappeler à chaque hit
        IpGeolocation::updateOrCreate(['ip_address' => $ip], $empty);

        return $empty;
    }

    /**
     * Détermine si une IP est privée / locale (non géolocalisable).
     */
    protected static function isPrivateIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
}
