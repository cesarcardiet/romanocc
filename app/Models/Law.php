<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Law extends Model
{
    protected $fillable = ['name', 'type'];

    protected $casts = [
        'type' => 'string',
    ];

    public function titles()
    {
        return $this->hasMany(Title::class, 'law_id', 'id');
    }

    // Scope para filtrar por tipo
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'law_id', 'id');
    }
}
