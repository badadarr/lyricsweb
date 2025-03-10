<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectLyric extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'project_lyrics';
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'project_name',
        'user_id', // Hapus jika tidak diperlukan
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lyrics()
    {
        return $this->hasMany(Lyric::class, 'project_name', 'project_name');
    }
}
