<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Board extends Model
{
    protected $fillable = ['name', 'description', 'created_by', 'is_private'];

    public function members()
    {
        return $this->hasMany(BoardMember::class);
    }

    public function columns()
    {
        return $this->hasMany(BoardColumn::class)->orderBy('position');
    }

    public function cards()
    {
        return $this->hasMany(BoardCard::class);
    }
}
