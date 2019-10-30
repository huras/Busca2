<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; //use if soft deletes

class Cliente extends Model
{
    use SoftDeletes; //user if soft deletes

    protected $table = 'clientes'; //table name

    protected $fillable = ['nome', 'foto', 'data', 'sexo', 'dinheiro', 'contador', 'documento', 'conteudo']; //campos para serem preenchidos em uma massStore
      //pode usar $guarded pra dizer que todos os campos que não estão no array, deverão ser lidos num massStore
        //usar $guarded xor $fillable

    public function itens()
    {
    	return $this->hasMany(AccordionItem::class);
    }
}
