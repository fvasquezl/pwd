<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class);
    }
}
