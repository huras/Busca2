<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Validator;

class ClientesController extends Controller
{
  public function create(Request $request){
    $form = $request->all();
  
    $val = $this->validateRequest($form);
    //dd($val->errors());
    if($form['g-recaptcha-response'] == null){
          return redirect()->action('TesteDeSiteController@showForm')
                  ->withInput()
                  ->withErrors($val)
                  ->with(['error-recaptcha' => true]);
      } else if ($val->fails()) {
          return redirect()->action('TesteDeSiteController@showForm')
                  ->withInput()
                  ->withErrors($val)
                  ->with(['error-recaptcha' => true]);
      } else {

      }
    
      return view('pages/teste-seu-site')->with(['form-success' => true]);;
  }

  private function validateRequest($request)
  {
    $rules = [
        'site' => 'required|url',
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
        'url' => 'Endereço inválido',
    ];

    return Validator::make($request, $rules, $messages);
  }
}
