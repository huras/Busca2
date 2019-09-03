<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Validator;

class ClientesController extends Controller
{
    //Store form a single field
    public function singleAssignment(Request $request){
        $cliente = new Cliente;
        $cliente->name = $request->name;
        $cliente->save();
    
        return redirect()
                ->action('ClientesController@list')
                ->with([
                'message' => 'Cliente criado com sucesso!',
                'alertType' => 'bg-success'
        ]);
    }

    //Stores from multiple fields
        //Requires $fillable in the model
    public function massAssignment(Request $request){
        $cliente = Cliente::create($request->all());
    
        return redirect()
                ->action('ClientesController@list')
                ->with([
                'message' => 'Cliente criado com sucesso!',
                'alertType' => 'bg-success'
        ]);
    }
}
