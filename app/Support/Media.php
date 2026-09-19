<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Media
{
    /**
     * Transforme un chemin d'image stocké en URL absolue servable.
     *
     * - null / vide            -> null
     * - déjà une URL/data URI  -> renvoyé tel quel
     * - chemin relatif         -> URL absolue via le disque "public" (basée sur APP_URL)
     */
    public static function url(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }

        if (preg_match('#^(https?://|data:)#i', $path)) {
            return $path;
        }

        $clean = ltrim($path, '/');

        // Le chemin peut déjà contenir le préfixe "storage/"
        if (str_starts_with($clean, 'storage/')) {
            $clean = substr($clean, strlen('storage/'));
        }

        return Storage::disk('public')->url($clean);
    }
}
