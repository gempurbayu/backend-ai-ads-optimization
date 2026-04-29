<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesPage extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'features_input',
        'audience',
        'price',
        'unique_selling_points',
        'template',
        'content',
        'export_html',
    ];

    protected $casts = [
        'features_input' => 'array',
        'content' => 'array',
    ];
}
