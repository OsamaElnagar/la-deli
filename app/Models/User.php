<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;


class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'role',
        'is_active',
        'address',
        'city',
        'state',
        'zip',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean'

        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
    public function branches()
    {
        return $this->belongsToMany(Branch::class, 'branch_user');
    }

    public function createdOrders()
    {
        return $this->hasMany(Order::class, 'created_by');
    }

    public function assignedOrders()
    {
        return $this->hasMany(Order::class, 'pharmacist_id');
    }

    public function deliveryOrders()
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    public function driverStatus()
    {
        return $this->hasOne(DriverStatus::class, 'driver_id');
    }

    // Helper methods
    public function isPharmacist()
    {
        return $this->hasRole('pharmacist');
    }

    public function isDriver()
    {
        return $this->hasRole('driver');
    }

    public function isFeeder()
    {
        return $this->hasRole('feeder');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    /**
     * Route notifications for the FCM channel.
     *
     * @return string|array
     */
    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }
}
