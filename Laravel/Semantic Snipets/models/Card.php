<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; //use if soft deletes

//php artisan make:model Card
class Card extends Model
{
    use SoftDeletes; //user if soft deletes

    protected $table = 'cards';

    //Overrides the primaryKey assumption laravel has that it's name is simply 'id'
    protected $primaryKey = 'card_id'; 

    //Overrides the definition that the primary key is an auto incremented integer
    //public $incrementing = false;

    //Must use if the primary key is not an int
    //protected $keyType = 'string';

    //By default Laravel expectes an created_at and updated_at column, use this to override it
    //public $timestamps = false;

    //Overrides the created_at and updated_at standard name
    //const CREATED_AT = 'creation_date';
    //const UPDATED_AT = 'last_update';

    //By default, all Eloquent models will use the default database connection configured for your application. use it to override
    //protected $connection = 'connection-name';

    //Define an default value for the fields
    protected $attributes = [
        'banned' => false,
    ];

    
}
