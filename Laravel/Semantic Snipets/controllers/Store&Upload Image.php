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

    public function upload(Request $request) {
        $folder_id = $request->folder_id;

        foreach($request->file('file') as $key => $file){

            $fileName = $file->getClientOriginalName();
            $absoluteFileSize = $file->getSize();

            //Solve file name and location
            $destinationPath = 'public/file/'; // upload path
            $profilefile = date('YmdHis') . $key . "." . $file->getClientOriginalExtension();
            $file->move($destinationPath, $profilefile);

            //Solve file size
            $base = log($absoluteFileSize, 1024);
            $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
            $fileSize = round(pow(1024, $base - floor($base)), 2) .' '. $suffixes[floor($base)];

            $path_parts = pathinfo($fileName);

            $newFile = [
                'name' => $path_parts['filename'],
                'filename' => $profilefile,
                'size' => $fileSize,
                'folder_id' => $folder_id,
                'absoluteSize' => $absoluteFileSize,
                'views' => 0,
            ];

            File::create($newFile);
        }

        return back();
    }

    public function download($id) {
        $targetFile = File::find($id);

        $file = public_path()."\\public\\file\\".$targetFile->filename;
        $fileExt = pathinfo($targetFile->filename, PATHINFO_EXTENSION);
        $headers = array(
            'Content-Type: application/'.$fileExt ,
        );
        return response()->download($file, $targetFile->name.'.'.$fileExt, $headers);
    }
}
