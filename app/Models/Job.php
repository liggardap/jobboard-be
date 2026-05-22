<?php

namespace App\Models;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property string $title
 * @property string $description
 * @property string $category
 * @property EmploymentType $employment_type
 * @property string|null $location_city
 * @property string $location_country
 * @property bool $is_remote
 * @property int|null $salary_min
 * @property int|null $salary_max
 * @property string $currency
 * @property JobStatus $status
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property-read Company $company
 */
class Job extends BaseModel
{
    protected function casts(): array
    {
        return [
            'status' => JobStatus::class,
            'employment_type' => EmploymentType::class,
            'is_remote' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function toSearchArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'employment_type' => $this->employment_type->value,
            'location_city' => $this->location_city,
            'location_country' => $this->location_country,
            'is_remote' => $this->is_remote,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'published_at' => $this->published_at?->toIso8601String(),
            'company' => [
                'id' => $this->company->id,
                'name' => $this->company->name,
                'industry' => $this->company->industry,
            ],
        ];
    }
}
