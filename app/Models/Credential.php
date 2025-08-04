<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    /**
     * Relación: destinos con los que se ha compartido esta credencial.
     */
    public function credentialShares()
    {
        return $this->hasMany(CredentialShare::class);
    }
    /** @use HasFactory<\Database\Factories\CredentialFactory> */
    use HasFactory;


    protected $fillable = [
        'username',
        'password',
        'description',
        'user_id',
        'category_id',
    ];


    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function canBeShared(): bool
    {
        return $this->category?->name !== 'Personal';
    }
}
