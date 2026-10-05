<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Record extends Model
{
    protected $fillable = [
        'title',
        'description',
        'status',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeVisibleTo(
        Builder $query,
        User $user
    ): Builder {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }
}
