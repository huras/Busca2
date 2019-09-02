<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Validator;

class ClientesController extends Controller
{
    public function create(Request $request){
		
      $val = $this->validateRequest($request->all());
      if ($val->fails()) {
          return redirect()->route('landingpage')
              ->withErrors($val)
              ->withInput()
              ->with([
                'create-modal-open' => true]);
      }
      
      Cliente::create($request->all());
  
      return redirect()
              ->action('ClientesController@list')
        ->with([
          'message' => 'Cliente criado com sucesso!',
          'alertType' => 'bg-success'
      ]);
  }

    private function validateRequest($request)
    {
        $rules = [
            'nome' => 'required',
            'email' => 'required|email',            
            'nascimento' => 'date_format:d/m/Y',
        ];

        $messages = [
            'required' => 'Campo obrigatório',
            'email' => 'Email inválido',
            'numeric' => 'Este campo só aceita valores numéricos',
            'unique' => 'Cadastro já existente com esta informação',
            'date_format' => 'Data inválida',
        ];

        return Validator::make($request, $rules, $messages);
    }
	
}
