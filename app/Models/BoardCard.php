<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardCard extends Model
{
    protected $fillable = ['column_id', 'board_id', 'title', 'description', 'credentials_data', 'position', 'created_by'];

    protected $casts = [
        'credentials_data' => 'encrypted',
    ];

    public function column()
    {
        return $this->belongsTo(BoardColumn::class, 'column_id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }
}
