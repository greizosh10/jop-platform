<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone',
        'address', 'skills', 'telegram_username',
        'company_name', 'tahass', 'website_url', 'profile_image'
    ];


    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'integer',
        ];
    }
    public function experiences() {
    return $this->hasMany(Experience::class);
}

public function educations() {
    return $this->hasMany(Education::class);
}

public function cvSummary() {
    return $this->hasOne(CvSummary::class);
}
}
