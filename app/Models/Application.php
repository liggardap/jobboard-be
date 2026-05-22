<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $job_id
 * @property int $user_id
 * @property string|null $cover_letter
 * @property ApplicationStatus $status
 * @property Carbon $applied_at
 * @property Carbon|null $updated_at
 * @property-read Job $job
 * @property-read User $user
 */
class Application extends BaseModel
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'status' => ApplicationStatus::class,
            'applied_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
