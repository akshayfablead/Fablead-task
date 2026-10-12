<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserImportFailure extends Model
{
    protected $guarded = [];

    public function userImport(): BelongsTo
    {
        return $this->belongsTo(UserImport::class);
    }
}
