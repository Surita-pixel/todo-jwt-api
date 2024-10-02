<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notas extends Model
{
    use HasFactory;
    protected $fillable =[
        'title',
        'description',
        'user_id',
        'labels',
        'image_path',
        'expiration_date',
    ];
    public function user()
    {
        return $this->belongsto(User::class, 'user_id');
    }
    protected $guarded = ['id'];    
}
