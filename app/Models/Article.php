<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $table = 'articles';

    protected $fillable = [
        'article_title',
        'content',
        'publication_date',
        'publisher_name',
        'admin_id',
        'category_id',
    ];

    public function category()
    {
        return $this->hasMany(Psikolog::class, 'category_id');
    }

    public function article_category()
    {
        return $this->belongsTo(ArticleCategory::class, 'category_id', 'id');
    }
}