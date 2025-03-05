<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lyric extends Model
{
    use HasFactory, SoftDeletes;
    // Nama tabel yang terkait dengan model
    protected $table = 'lyrics';

    // Kolom yang dapat diisi (mass assignable)
    protected $fillable = [
        'title',
        'artist',
        'lyric',
        'language',
        'project_name',
    ];

    // Relasi ke model ProjectLyric (satu Lyric dimiliki oleh satu ProjectLyric)
    public function projectLyric()
    {
        return $this->belongsTo(ProjectLyric::class, 'project_name', 'project_name');
    }

}
