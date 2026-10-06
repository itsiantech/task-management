<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardCard extends Model
{
    protected $fillable = ['column_id', 'board_id', 'title', 'description', 'credentials_data', 'position', 'created_by'];

    protected function casts(): array
    {
        return [
            // Stored encrypted (APP_KEY); exposed as an array: username / password / url.
            'credentials_data' => 'encrypted:array',
        ];
    }

    public function column()
    {
        return $this->belongsTo(BoardColumn::class, 'column_id');
    }

    public function board()
    {
        return $this->belongsTo(Board::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function hasCredentials(): bool
    {
        return filled($this->credentials_data);
    }
}
