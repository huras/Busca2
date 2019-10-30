<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Validator;

class ClientesController extends Controller
{
    //Store form a single field
    public function singleUpdate(Request $request, $id){
        $cliente = Cliente::find($id);
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
    public function massUpdate(Request $request, $id) {
        Cliente::where('id', $id)
          ->update($request->all());
    
        return redirect()
                ->action('ClientesController@list')
                ->with([
                'message' => 'Cliente criado com sucesso!',
                'alertType' => 'bg-success'
        ]);
    }
}
