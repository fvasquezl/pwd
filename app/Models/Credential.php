<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    /** @use HasFactory<\Database\Factories\CredentialFactory> */
    use HasFactory;


    protected $fillable = [
        'username',
        'password',
        'description',
        'user_id',
        'category_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
