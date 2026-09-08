<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evaluation extends Model
{
    /** @use HasFactory<\Database\Factories\EvaluationFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    public function evaluateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluateur_id');
    }
 
    public function evalue(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evalue_id');
    }
 
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
