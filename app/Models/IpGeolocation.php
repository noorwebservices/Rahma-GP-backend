<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IpGeolocation extends Model
{
    protected $table = 'ip_geolocations';

    protected $primaryKey = 'ip_address';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'ip_address',
        'country',
        'country_code',
        'city',
    ];
}
