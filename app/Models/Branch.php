<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Branch extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'name',
        'code',
        'type',
        'address',
        'phone',
        'email',
        'coordinates',
        'is_active'
    ];

    protected $casts = [
        'coordinates' => 'array',
        'is_active' => 'boolean'
    ];

    // Branch types constants
    const TYPE_PHARMACY = 'pharmacy';
    const TYPE_WAREHOUSE = 'warehouse';
    const TYPE_BRANCH = 'branch';

    public static function getTypes(): array
    {
        return [
            self::TYPE_PHARMACY => 'Pharmacy',
            self::TYPE_WAREHOUSE => 'Warehouse',
            self::TYPE_BRANCH => 'Branch',
        ];
    }

    // Relationships
    public function sourceOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'source_branch_id');
    }

    public function destinationOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'destination_branch_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'branch_user');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePharmacies($query)
    {
        return $query->where('type', self::TYPE_PHARMACY);
    }

    public function scopeWarehouses($query)
    {
        return $query->where('type', self::TYPE_WAREHOUSE);
    }

    public function scopeBranches($query)
    {
        return $query->where('type', self::TYPE_BRANCH);
    }

    // Auto-generate code based on name and type
    public static function generateCode(string $name, string $type): string
    {
        $prefix = strtoupper(substr($type, 0, 3));
        $namePart = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        $namePart = substr($namePart, 0, 5);

        $baseCode = $prefix . '-' . $namePart;

        // Check if code exists and append number if needed
        $counter = 1;
        $code = $baseCode;

        while (static::where('code', $code)->exists()) {
            $code = $baseCode . '-' . $counter;
            $counter++;
        }

        return $code;
    }

    // Media Collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
    }

    // Accessors
    public function getImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('images');
    }

    public function getDocumentUrlsAttribute(): array
    {
        return $this->getMedia('documents')->map(fn($media) => $media->getUrl())->toArray();
    }

    public function getCoordinatesLatAttribute(): ?float
    {
        return $this->coordinates['lat'] ?? null;
    }

    public function getCoordinatesLngAttribute(): ?float
    {
        return $this->coordinates['lng'] ?? null;
    }

    // Mutators
    public function setCoordinatesAttribute($value): void
    {
        if (is_string($value)) {
            $this->attributes['coordinates'] = json_encode(['lat' => null, 'lng' => null]);
        } else {
            $this->attributes['coordinates'] = json_encode($value);
        }
    }
}
