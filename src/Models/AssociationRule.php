<?php

namespace RennyPasardesa\Apriori\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssociationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'products',
        'recommendation',
        'confidence',
        'lift'
    ];

    protected $casts = [
        'products' => 'array',
    ];
}
