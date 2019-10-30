<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Validator;

class ClientesController extends Controller
{
    //Salva um campo com imagem
    public function massAssignment(Request $request){
        //Valida e renomeia a imagem
        $this->validate($request, ['cover' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048']);

        $image = $request->file('cover');
        
        //Muda o nome da imagem
        $new_name = rand(). '.' .$image->getClientOriginalExtension();
        $userData = $request->all();
        $userData['cover'] = $new_name;

        //Salva os dados do usuário, o campo de imagem agora é só o nome e extensão dela
        $user = User::create($userData);

        //Move a imagem pro diretorio correto
        $image->move(public_path("files/clientes/".$user->id), $new_name);
    
        return redirect()
                ->action('ClientesController@list')
                ->with([
                'message' => 'Cliente criado com sucesso!',
                'alertType' => 'bg-success'
        ]);
    }
}
