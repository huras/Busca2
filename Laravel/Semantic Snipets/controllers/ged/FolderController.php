<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Folder;
use App\Models\FolderPermission;
use App\Models\File;
use App\Group;
use App\Healthcare;
use App\User;

use App\Http\Controllers\FileController;
use Auth, ZipArchive;
use File as Filesystem; // Illuminate\Filesystem\Filesystem

class FolderController extends Controller
{
    public function totalSearchAjax(Request $request){
        // $search = $request->search;
        // $search_location = $request->search_location;

        // //Busca em pasta
        // if($search_location){
        //     $folders = Folder::where('name', 'LIKE', '%'.$search.'%')->where('parent_id', $search_location)->get();
        //     $files = File::where('name', 'LIKE', '%'.$search.'%')->where('folder_id', $search_location)->get();
        // } else {
        //     $folders = Folder::where('name', 'LIKE', '%'.$search.'%')->get();
        //     $files = File::where('name', 'LIKE', '%'.$search.'%')->get();
        // }

        // return response()->json(json_encode(['files' => $files, 'folders' => $folders]), '200');
        return response()->json(json_encode('not implemented'), '500');
    }

    public function totalSearch(Request $request){
        $isSearchResult = true;
        $search = $request->search;
        $search_location = $request->search_location;
        $searchFolder = Folder::find($search_location);

        $user = Auth::user();

        //Obtem as pastas que o usuário tem acesso no formato de array
        $allowed_access_folders = $user->accessFolders->pluck('id');
        $allowed_folders = [];
        foreach($allowed_access_folders as $item){
            $allowed_folders[] = $item;
        }

        //Obtem os arquivos que o usuário tem acesso no formato de array
        $allowed_access_files = $user->accessFiles->pluck('id');
        $allowed_files = [];
        foreach($allowed_access_files as $item){
            $allowed_files[] = $item;
        }

        // Busca de FOLDERS
        $resultFolders = Folder::whereIn('id', $allowed_access_folders)->where('name', 'LIKE', '%'.$search.'%');
        if($search_location){
            $possibleFoldersID = array_merge($this->getFoldersIDRecursively($searchFolder), [$searchFolder->id]);
            $resultFolders->whereIn('parent_id', $possibleFoldersID);
        }
        $resultFolders = $resultFolders->get();
        $folders = $resultFolders;

        // Busca de FILES
        $resultFiles = File::whereIn('id', $allowed_access_files)->where('name', 'LIKE', '%'.$search.'%');
        if($search_location){
            $resultFiles->whereIn('folder_id', $possibleFoldersID);
        }
        $resultFiles = $resultFiles->get();
        $files = $resultFiles;

        //Adiciona alguns dados aos arquivos
        foreach($files as $key => $file) {
            $latestVersion = $file->latestVersion()->first();
            if(isset($latestVersion)) {
                $ext = pathinfo($latestVersion->filename, PATHINFO_EXTENSION);

                $imageFormats = ['png', 'jpg', 'bmp', 'gif', 'tif', 'jpeg'];
                $videoFormats = ['webm', 'mkv', 'flv', 'vob', 'ogg', 'ogv',
                            'avi', 'wmv', 'rm', 'webm', 'yuv', 'rmvb', 'asf', 'amv',
                            'mp4', 'm4p', 'mpg', 'mp2', 'mpeg', 'mpe', 'mpv', 'm4v',
                            '3gp', '3g2'];
                $documentFormats = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

                $tipo = '?';

                if (in_array ($ext, $imageFormats)) {
                    $tipo = 'Imagem';
                } else if (in_array ($ext, $videoFormats)) {
                    $tipo = 'Video';
                } else if (in_array ($ext, $documentFormats)){
                    $tipo = 'Documento';
                }

                $file->ext = $ext;
                $file->tipo = $tipo;
                $file->filename = $latestVersion->filename;
            }
            $files[$key] = $file;
        }

        //Obtem os root folders que podem ser vistos pelo usuário
        $rootFolders = Folder::whereIn('id', $allowed_access_folders)->where('parent_id', null)->get();
        $searchQuery = $search;
        return view('components/fileExplorer', compact('folders', 'files', 'rootFolders', 'isSearchResult', 'allowed_folders', 'allowed_files', 'searchFolder', 'searchQuery'));
    }

    function getFoldersIDRecursively($folder){
        $retorno = [];
        foreach($folder->childrenFolders as $child){
            $retorno[] = $child->id;
            $retorno = array_merge($retorno, $this->getFoldersIDRecursively($child));
        }

        return $retorno;
    }

    public function mayDownloadZip(Request $request, $folder_id){
        $folder = Folder::find($folder_id);

        if ($folder) {
            return response()->json([
                'status' => $this->recursiveMayDownloadZip($folder->id),
                'message' => 'A pasta informada não contém arquivos!']
            , 200);
        }
        else {
            return response()->json([
                'status' => false,
                'message' => 'A pasta informada não existe']
            , 200);
        }
    }
    function recursiveMayDownloadZip($folder_id){
        $folder = Folder::find($folder_id);

        if($folder){
            if(count($folder->childrenFiles) > 0){
                return true;
            } else {
                $retorno = false;
                foreach($folder->childrenFolders as $f){
                    $retorno = $retorno || $this->recursiveMayDownloadZip($f->id);
                    if($retorno)
                        break;
                }

                return $retorno;
            }
        } else {
            return false;
        }
    }

    public function downloadZip(Request $request, $folder_id){
        $folder = Folder::find($folder_id);

        if($folder){

            $targetFiles = $folder->childrenFiles;
            $targetFilenames = [];
            foreach($targetFiles as $file) {
                $targetFilenames[] = $file->latestVersion()->first()->filename;
            }

            $zip = new ZipArchive;
            $fileName = $folder->name.'.zip';

            if ($zip->open(public_path($fileName), ZipArchive::CREATE) === TRUE)
            {
                $files = Filesystem::files(public_path('public/file'));

                foreach ($files as $key => $value) {
                    if(in_array($value->getFilename(), $targetFilenames)) {
                        $relativeNameInZipFile = basename($value);
                        $zip->addFile($value, $relativeNameInZipFile);
                    }
                }

                $zip->close();
            }

            return response()->download(public_path($fileName), $fileName, ['Content-Type: application/octet-stream'])->deleteFileAfterSend();
        }
    }

    public function list() {
        //Obtem os root folders que podem ser vistos pelo usuário
        $rootFoldersAccesses = $this->getUserRootFolders(Auth::user());
        $rootFolders = $rootFoldersAccesses['rootFolders'];
        $allowed_folders = $rootFoldersAccesses['rootFoldersIDS'];
        $folders = $rootFolders;

        return view('components/fileExplorer', compact('allowed_folders', 'rootFolders', 'folders'));
    }

    function getUserRootFolders($user){
        $user_allowed_folders = Auth::user()->accessFolders->pluck('id');
        $allowed_folders = [];
        foreach($user_allowed_folders as $item){
            $allowed_folders[] = $item;
        }

        $rootFolders = [];
        $rootFoldersIDs = [];
        foreach(Auth::user()->accessFolders()->orderBy('name')->get() as $folder){
            if(!$folder->parent_id){
                if(!in_array($folder->id, $rootFoldersIDs)){
                    $rootFolders[] = $folder;
                    $allowed_folders[] = $folder->id;
                    $rootFoldersIDs[] = $folder->id;
                }
            } else {
                if(!in_array($folder->id, $rootFoldersIDs)){
                    if(!in_array($folder->parent_id, $allowed_folders)){
                        $rootFolders[] = $folder;
                        $allowed_folders[] = $folder->id;
                        $rootFoldersIDs[] = $folder->id;
                    }
                }
            }
        }

        //Procura por arquivos orfãos, ou seja, um arquivo que foi compartilhado mas cuja a pasta em que se encontra não foi compartilhada
        $user_allowed_files = Auth::user()->accessFiles;
        $user_allowed_files_ids = $user_allowed_files->pluck('id');
        $allowed_files = [];
        foreach($user_allowed_files_ids as $item) {
            $allowed_files[] = $item;
        }
        foreach(Auth::user()->accessFiles as $file) {

            if(!in_array($file->folder_id, $allowed_folders)) {
                $rootFolders[] = Folder::find($file->folder_id);
                $allowed_folders[] = $file->folder_id;
            }
        }

        // Folder::whereIn('id', $user_allowed_folders)->where('parent_id', null)->get();
        return ['rootFolders' => $rootFolders, 'rootFoldersIDS' => $allowed_folders];
    }

    public function view($id) {
        //Obtem os root folders que podem ser vistos pelo usuário
        $rootFoldersAccesses = $this->getUserRootFolders(Auth::user());
        $rootFolders = $rootFoldersAccesses['rootFolders'];
        $allowed_folders = $rootFoldersAccesses['rootFoldersIDS'];

        if(in_array($id, $allowed_folders)){
            //Obtem a pasta a ser vizualizada
            $folder = Folder::find($id);

            $contents = $this->getFilesAndFoldersRespectingPermissions(Auth::user()->id, $id);
            $folders = $contents['folders'];
            $files = $contents['files'];

            return view('components/fileExplorer', compact('folder', 'folders', 'files', 'allowed_folders', 'rootFolders'));
        }
        else {
            return redirect()->action('FolderController@list');
        }
    }

    public function layout(){
        return view('components/layout');
    }

    public function create(Request $request) {
        $newFolder = [
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'user_id' => Auth::user()->id
        ];

        $folder = Folder::create($newFolder);

        $this->SetFolderInitialPermitions($folder);

        return back();
    }

    public function rename(Request $request ,$id){
        $targetFolder = Folder::find($id);
        $targetFolder->name = $request->name;
        $targetFolder->save();

        return back();
    }


    public function delete($id){
        $group = Group::where('folder_id', $id)->first();

        if(!$group){
            $this->recursiveFolderDeletion($id);
        }

        return back();
    }
    public function deleteAjax($id){
        $group = Group::where('folder_id', $id)->first();

        if(!$group){
            $this->recursiveFolderDeletion($id);
            return response()->json([
                'success' => true,
                'message' => 'Pasta removida com sucesso!'
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Não é possível remover a pasta de um grupo!'
        ], 200);
    }

    function recursiveFolderDeletion($id) {
        $targetFolder = Folder::find($id);

        $fileController = new FileController();

        foreach($targetFolder->childrenFiles as $file) {
            $fileController->deleteFile($file->id);
        }

        foreach($targetFolder->childrenFolders as $folder) {
            $this->recursiveFolderDeletion($folder->id);
        }

        $group = Group::where('folder_id', $id)->first();
        if(!$group){
            FolderPermission::where('folder_id', $targetFolder->id)->delete();
            $targetFolder->delete();
        }
    }

    function uploadFolderByPathAjax(Request $request) {
        $parent_folder = Folder::find($request->parent_id);
        $target_parent_folder = $parent_folder;
        $addresses = explode(',',$request->address);

        $currentLevel = 0;
        $targetLevel = $request->deepth;
        while($currentLevel < $targetLevel){
            $found = false;
            foreach($target_parent_folder->childrenFolders as $folder){
                if($folder->name == $addresses[$currentLevel]) {
                    $target_parent_folder = $folder;
                    $currentLevel++;
                    $found = true;
                    break;
                }
            }

            if(!$found){
                break;
            }
        }

        $newFolder = Folder::create(
            [
                'name' => $request->folder_name,
                'parent_id' => $target_parent_folder->id,
                'user_id' => Auth::user()->id
            ]
        );

        $this->SetFolderInitialPermitions($newFolder);

        $newFolder->userPermissions()->attach($newFolder->creator->id);

        return response()->json($newFolder->id,200);
    }
    function SetFolderInitialPermitions($folder){
        if(isset($folder->parent_id)){
            $parent = Folder::find($folder->parent_id);
            $folder_permissions = FolderPermission::where('folder_id', $parent->id)->get();
            foreach ($folder_permissions as $permission) {
                FolderPermission::create(['folder_id' => $folder->id, 'user_id' => $permission->user_id, 'group_id' => $permission->group_id, 'healthcare_id' => $permission->healthcare_id]);
            }
        } else {
            FolderPermission::create(['folder_id' => $folder->id, 'user_id' => $folder->creator->id]);
        }
    }

    function upload(Request $request, $parent_id) {
        $folder = Folder::create(
            [
                'name' => $request->folderName,
                'parent_id' => $parent_id,
            ]
        );

        $folder_id = $folder->id;

        foreach($request->file('folder') as $key => $file){

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
                'user_id' => Auth::user()->id
            ];

            File::create($newFile);
        }

        return back();
    }

    public function moveToFolder(Request $request, $folder_id){
        $targetFolder = Folder::find($folder_id);

        $targetFolderID = $request->parent_id;
        $targetFolder->parent_id = $targetFolderID;
        $targetFolder->save();

        return back();
    }

    public function viewAjax(Request $request, $folder_id){
        $user = Auth::user();
        $folder = Folder::find($folder_id);

        $contents = $this->getFilesAndFoldersRespectingPermissions($user->id, $folder_id);

        return response()->json([
            'files' => $contents['files'],
            'folders' => $contents['folders']
        ], 200);
    }
    function getFilesAndFoldersRespectingPermissions($user_id, $folder_id){
        $user = User::find($user_id);
        $folder = Folder::find($folder_id);

        $allowedFilesIDS = [];
        $allowedFiles = $user->accessFiles->pluck('id');
        foreach($allowedFiles as $item){
            $allowedFilesIDS[] = $item;
        }
        $files = $folder->childrenFiles()->whereIn('id', $allowedFilesIDS)->get();

        $allowedFoldersIDS = [];
        $allowedFolders = $user->accessFolders->pluck('id');
        foreach($allowedFolders as $item){
            $allowedFoldersIDS[] = $item;
        }
        $folders = $folder->childrenFolders()->whereIn('id', $allowedFoldersIDS)->get();

        foreach($files as $key => $file) {
            $latestVersion = $file->latestVersion()->first();
            if(isset($latestVersion)) {
                $ext = pathinfo($latestVersion->filename, PATHINFO_EXTENSION);

                $imageFormats = ['png', 'jpg', 'bmp', 'gif', 'tif', 'jpeg'];
                $videoFormats = ['webm', 'mkv', 'flv', 'vob', 'ogg', 'ogv',
                            'avi', 'wmv', 'rm', 'webm', 'yuv', 'rmvb', 'asf', 'amv',
                            'mp4', 'm4p', 'mpg', 'mp2', 'mpeg', 'mpe', 'mpv', 'm4v',
                            '3gp', '3g2'];
                $documentFormats = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

                $tipo = '?';

                if (in_array ($ext, $imageFormats)) {
                    $tipo = 'Imagem';
                } else if (in_array ($ext, $videoFormats)) {
                    $tipo = 'Video';
                } else if (in_array ($ext, $documentFormats)){
                    $tipo = 'Documento';
                }

                $file->ext = $ext;
                $file->tipo = $tipo;
                $file->filename = $latestVersion->filename;
            }
            $files[$key] = $file;
        }

        return [
            'files' => $files,
            'folders' => $folders
        ];
    }

    public function moveToFolderAjax(Request $request, $folder_id){
        $targetFolder = Folder::find($folder_id);

        $targetFolderID = $request->parent_id;
        $targetFolder->parent_id = $targetFolderID;
        $targetFolder->save();

        return response()->json(true, 200);
    }

    /*
    *
    *   Shares a single folder with an user, group or healthcare
    *
    */
    public function shareSingleFolder(Request $request){
        $credentials = $request->all();

        switch ($credentials['type']){
            case 'group':
                $users = Group::find($credentials['group_id'])->users()->get()->pluck('id');

                $folder = Folder::find($credentials['folder_id'])->userPermissions()->attach($users, ['group_id'=>$credentials['group_id']]);
                break;
            case 'healthcare':
                $users = Healthcare::find($credentials['healthcare_id'])->users()->get()->pluck('id');

                $folder = Folder::find($credentials['folder_id'])->userPermissions()->attach($users, ['healthcare_id'=>$credentials['healthcare_id']]);
                break;
            default: //self
                $folder = Folder::find($credentials['folder_id'])->userPermissions()->attach($credentials['user_id']);
                break;
        }

        return response()->json(['Success'=>'folder shared'],200);
    }

    public function shareSingleFolderWithFiles(Request $request){
        $credentials = $request->all();

        $folder_id = $credentials['folder_id'];
        $files_id = Folder::find($folder_id)->childrenFiles()->get()->pluck('id');

        switch ($credentials['type']){
            case 'group':
                $group_id = $credentials['id'];
                $users = Group::find($group_id)->users()->get()->pluck('id');

                $folder = Folder::find($folder_id)->userPermissions()->attach($users, ['group_id'=>$group_id]);

                foreach($users as $user_id){
                    if(!empty($files_id)){
                        $user = User::find($user_id)->accessFiles()->attach($files_id,['group_id'=>$group_id]);
                    }
                }

                break;
            case 'healthcare':
                $healthcare_id = $credentials['id'];
                $users = Healthcare::find($healthcare_id)->users()->get()->pluck('id');

                $folder = Folder::find($folder_id)->userPermissions()->attach($users, ['healthcare_id'=>$healthcare_id]);

                foreach($users as $user_id){
                    if(!empty($files_id)){
                        $user = User::find($user_id)->accessFiles()->attach($files_id,['healthcare_id'=>$healthcare_id]);
                    }
                }

                break;
            default: //self
                $user_id = $credentials['id'];
                $folder = Folder::find($credentials['folder_id'])->userPermissions()->attach($user_id);

                if(!empty($files_id)){
                    $user = User::find($user_id)->accessFiles()->attach($files_id);
                }

                break;
        }

        return response()->json(['Success'=>'folder shared'],200);
    }

    /*
    *
    *   Removes one folder access permission for a every user, group or healthcare
    *
    */
    public function removeShareSingleFolder(Request $request){
        $credentials = $request->all();

        switch ($credentials['type']){
            case 'group':

                $folder = Folder::find($credentials['folder_id'])->groupPermissions()->detach($credentials['group_id']);
                break;
            case 'healthcare':

                $folder = Folder::find($credentials['folder_id'])->groupPermissions()->detach($credentials['healthcare_id']);
                break;
            default: //self
                $folder = Folder::find($credentials['folder_id'])->userPermissions()
                                                                 ->wherePivot('group_id', null)
                                                                 ->wherePivot('healthcare_id', null)
                                                                 ->detach($credentials['user_id']);
                break;
        }

        return response()->json(['Success'=>'folder permission removed'],200);

        return response()->json([$credentials],200);
        // return response()->json(['Success'=>'folder shared'],200);
    }

    public function removePermition(Request $request, $folder_id){
        $folder = Folder::find($folder_id);
        $type = $request->type;
        if($type == 'user'){
            $folder->userPermissions()->detach($request->id);
        } else if($type == 'group'){
            $folder->groupPermissions()->detach($request->id);
        } else if($type == 'healthcare'){
            $folder->healthcarePermissions()->detach($request->id);
        }

        return response()->json(true, '200');
    }

    public function removeShareSingleFolderWithFiles(Request $request){
        $credentials = $request->all();

        $folder_id = $credentials['folder_id'];
        $files_id = Folder::find($folder_id)->childrenFiles()->get()->pluck('id');

        switch ($credentials['type']){
            case 'group':
                $group_id = $credentials['id'];

                $group = Group::find($group_id)->accessFolders()->detach($folder_id);

                if(!empty($files_id)){
                    $group = Group::find($group_id)->accessFiles()->detach($files_id);
                }

                break;
            case 'healthcare':
                $healthcare_id = $credentials['id'];

                $healthcare = Healthcare::find($healthcare_id)->accessFolders()->detach($folder_id);

                if(!empty($files_id)){
                    $healthcare = Healthcare::find($healthcare_id)->accessFiles()->detach($files_id);
                }

                break;
            default: //self
                $user_id = $credentials['id'];
                $folder = User::find($user_id)->accessFolders()->wherePivot('group_id', null)
                                                               ->wherePivot('healthcare_id', null)
                                                               ->detach($folder_id);

                if(!empty($files_id)){
                    $user = User::find($user_id)->accessFiles()->wherePivot('group_id', null)
                                                               ->wherePivot('healthcare_id', null)
                                                               ->detach($files_id);
                }
                break;
        }

        return response()->json(['Success'=>'folder shared'],200);
    }
    public function sharedWithWhoInfo(Request $request, $folder_id){
        $folder = Folder::find($folder_id);

        $users = $folder->userPermissions()->distinct()->get();
        $groups = $folder->groupPermissions()->distinct()->get();
        foreach($groups as $key => $item){
            $item->usersIn = count($item->users);
            $groups[$key] = $item;
        }
        $healthcares = $folder->healthcarePermissions()->distinct()->get();
        foreach($healthcares as $key => $item){
            $item->usersIn = count($item->users);
            $healthcares[$key] = $item;
        }

        return response()->json(['users' => $users, 'groups' => $groups, 'healthcares' => $healthcares],200);
    }

    /*
    *
    *   Shares multiple folders with an user, group or healthcare
    *
    */
    public function shareMultipleFolders(Request $request){
        $credentials = $request->all();
        $folder_id = $credentials['folder_id'];

        switch ($credentials['type']){
            case 'group':
                if(!is_array ($credentials['id'])){
                    $group_ids=[$credentials['id']];
                }else{
                    $group_ids=$credentials['id'];
                }

                foreach($group_ids as $group_id){
                    $users = Group::find($group_id)->users()->get()->pluck('id');
                    foreach($users as $user_id){
                        $user = User::find($user_id);
                        $this->shareFoldersFilesRecursively($folder_id, $user, ['group_id'=>$group_id]);
                    }
                }

                break;
            case 'healthcare':
                if(!is_array ($credentials['id'])){
                    $healthcare_ids=[$credentials['id']];
                }else{
                    $healthcare_ids=$credentials['id'];
                }

                foreach($healthcare_ids as $healthcare_id){
                    $users = Healthcare::find($healthcare_id)->users()->get()->pluck('id');
                    foreach($users as $user_id){
                        $user = User::find($user_id);
                        $this->shareFoldersFilesRecursively($folder_id, $user, ['healthcare_id'=>$healthcare_id]);
                    }
                }

                break;
            default: //self
                if(!is_array ($credentials['id'])){
                    $users=[$credentials['id']];
                }else{
                    $users=$credentials['id'];
                }

                foreach ($users as $user_id){
                    $user = User::find($user_id);
                    $this->shareFoldersFilesRecursively($folder_id,$user,[]);
                }
                break;
        }

        return response()->json(['Success'=>'folder shared'],200);
    }

    public function shareFoldersFilesRecursively($folder_id, $user, $complement){
        //Fetch all files linked to current folder and shares them
        $files_id = Folder::find($folder_id)->childrenFiles()->get()->pluck('id');
        if(!empty($files_id)){
            if(!empty($complement)){
                $user->accessFiles()->attach($files_id,$complement);
            }else{
                $user->accessFiles()->attach($files_id);
            }

        }

        //link current folder to user
        if(!empty($complement)){
            $user->accessFolders()->attach($folder_id,$complement);
        }else{
            $user->accessFolders()->attach($folder_id);
        }

        //check For children
        $children = Folder::find($folder_id)->childrenFolders()->get()->pluck('id');

        //Recursively call for children and share its permission
        foreach($children as $child_id){
            $this->shareFoldersFilesRecursively($child_id, $user, $complement);
        }
    }

    /*
    *
    *   Removes multiple folder access permission for a every user, group or healthcare
    *
    */
    public function removeShareMultipleFolders(Request $request){
        $credentials = $request->all();
        $folder_id = $credentials['folder_id'];

        switch ($credentials['type']){
            case 'group':
                $group_id = $credentials['id'];
                if(!is_array ($credentials['id'])){
                    $groups=[$credentials['id']];
                }else{
                    $groups=$credentials['id'];
                }
                foreach ($groups as $group_id){

                    $group = Group::find($group_id)->users()->get()->pluck('id');
                    $this->removeSharedFoldersFilesRecursively($folder_id,$group,[]);

                }

                break;
            case 'healthcare':
                if(!is_array ($credentials['id'])){
                    $healthcares=[$credentials['id']];
                }else{
                    $healthcares=$credentials['id'];
                }

                foreach ($healthcares as $healthcare_id){

                    $healthcare = Healthcare::find($healthcare_id);
                    $this->removeSharedFoldersFilesRecursively($folder_id,$healthcare,[]);
                }

                break;
            default: //self
                if(!is_array ($credentials['id'])){
                    $users=[$credentials['id']];
                }else{
                    $users=$credentials['id'];
                }

                foreach ($users as $user_id){
                    $user = User::find($user_id);
                    $this->removeSharedFoldersFilesRecursively($folder_id,$user,[0=>[
                                                                            'id'=>'group_id',
                                                                            'value'=>null,
                                                                          ],
                                                                          1=>[
                                                                            'id'=>'healthcare_id',
                                                                            'value'=>null,
                                                                          ],
                                                                        ]);
                }
                break;
        }

        return response()->json(['Success'=>'folder permission removed'],200);
    }

    public function removeSharedFoldersFilesRecursively($folder_id, $obj, $where){
        //Fetch all files linked to current folder and shares them
        $files_id = Folder::find($folder_id)->childrenFiles()->get()->pluck('id');
        if(!empty($files_id)){
            if(!empty($where)){

                $obj_tmp = $obj->accessFiles();
                foreach($where as $option){
                    $obj_tmp = $obj_tmp->wherePivot($option['id'],$option['value']);
                }

                $obj_tmp->detach($files_id);
            }else{
                $obj->accessFiles()->detach($files_id);
            }
        }

        //link current folder to user
        if(!empty($where)){

            $obj_tmp = $obj->accessFolders();
            foreach($where as $option){
                $obj_tmp = $obj_tmp->wherePivot($option['id'],$option['value']);
            }

            $obj_tmp->detach($folder_id);
        }else{
            $obj->accessFolders()->detach($folder_id);
        }

        //check For children
        $children = Folder::find($folder_id)->childrenFolders()->get()->pluck('id');

        //Recursively call for children and share its permission
        foreach($children as $child_id){
            $this->removeSharedFoldersFilesRecursively($child_id, $obj, $where);
        }
    }

    public function getGraphData(Request $request){
        $statistics=[];

        //Get size statistics
        $healthcares = Healthcare::all();
        foreach ($healthcares as $healthcare){
            //fetch total used space by healthcare
            $size = $this->getFolderSizeRecursively($healthcare->folder_id,0);

            // $statistics[$healthcares->company_name] = $size;

            $data_block = [
                'name'=>$healthcare->company_name,
                'size'=>$size,
                'type'=>'Healthcare',
            ];
            array_push($statistics, $data_block);
        }

        $groups = Group::all();
        foreach ($groups as $group){
            //fetch total used space by healthcare
            $size = $this->getFolderSizeRecursively($group->folder_id,0);

            // $statistics[$group->group_name] = $size;

            $data_block = [
                'name'=>$group->group_name,
                'size'=>$size,
                'type'=>'Group',
            ];
            array_push($statistics, $data_block);
        }

        return response()->json($statistics,200);
    }

    public function getFolderSizeRecursively($folder_id, $folder_size){
        //List all files in folder
        $files = Folder::find($folder_id)->childrenFiles()->get();

        //Updates folder size for each file
        foreach($files as $file){
            $folder_size+=$file->latestVersion()->first()->absoluteSize;
        }

        //Get child folders
        $folder_childs = Folder::find($folder_id)->childrenFolders()->get()->pluck('id');
        foreach($folder_childs as $child_id){
            $folder_size = $this->getFolderSizeRecursively($child_id, $folder_size);
        }

        return $folder_size;
    }

    public function generalSearch(Request $request){
        $isSearchResult = true;
        $search = $request->search;
        $search_location = $request->search_location;
        $searchFolder = Folder::find($search_location);

        $user = Auth::user();

        //Obtem as pastas que o usuário tem acesso no formato de array
        $allowed_access_folders = $user->accessFolders->pluck('id');
        $allowed_folders = [];
        foreach($allowed_access_folders as $item){
            $allowed_folders[] = $item;
        }

        //Obtem os arquivos que o usuário tem acesso no formato de array
        $allowed_access_files = $user->accessFiles->pluck('id');
        $allowed_files = [];
        foreach($allowed_access_files as $item){
            $allowed_files[] = $item;
        }

        // Busca de FOLDERS
        $resultFolders = Folder::whereIn('id', $allowed_access_folders)->where('name', 'LIKE', '%'.$search.'%');
        if($search_location){
            $possibleFoldersID = array_merge($this->getFoldersIDRecursively($searchFolder), [$searchFolder->id]);
            $resultFolders->whereIn('parent_id', $possibleFoldersID);
        }
        $resultFolders = $resultFolders->get()->all();

        // Busca de FILES
        $resultFiles = File::whereIn('id', $allowed_access_files)->where('name', 'LIKE', '%'.$search.'%');
        if($search_location){
            $resultFiles->whereIn('folder_id', $possibleFoldersID);
        }
        $resultFiles = $resultFiles->get()->all();

        switch($user->role){
            case 'admin':
                // Busca de USERS
                $resultUsers = User::where('name','LIKE', '%'.$search.'%')
                                    ->orWhere('email','LIKE', '%'.$search.'%')
                                    ->orWhere('cpf','LIKE', '%'.$search.'%')->get()->all();


                //Busca de GROUPS
                $resultGroups = Group::where('group_name','LIKE', '%'.$search.'%')->get()->all();

                //Busca de HEALTHCARES
                $resultHealthcares = Healthcare::where('company_name','LIKE', '%'.$search.'%')
                                                ->orwhere('cnpj','LIKE', '%'.$search.'%')->get()->all();
                break;
            
            case 'master':
                // Busca de USERS
                $resultUsers = User::where('name','LIKE', '%'.$search.'%')
                                    ->orWhere('email','LIKE', '%'.$search.'%')
                                    ->orWhere('cpf','LIKE', '%'.$search.'%')->get()->all();

                //Busca de GROUPS
                $groupsUser = $user->groups()->wherePivot('admin',1)
                                            ->orWhere('groups.id_user',$user->id)
                                            ->get()->pluck('id')->all();
                $resultGroups = Group::whereIn('id',$groupsUser)
                                        ->where('group_name','LIKE', '%'.$search.'%')->get()->all();

                //Busca de HEALTHCARES
                $healthcareUser = $user->healthcares()->wherePivot('admin',1)
                                        ->orWhere('heathcares.id_user',$user->id)
                                        ->get()->pluck('id')->all();
                $resultHealthcares = Healthcare::whereIn('id',$healthcareUser)->where('company_name','LIKE', '%'.$search.'%')
                                                ->orwhere('cnpj','LIKE', '%'.$search.'%')->get()->all();
                break;

            default:
                $resultUsers=[];
                $resultGroups=[];
                $resultHealthcares=[];
                break;
        }

        // dd($resultFiles,$resultFolders,$resultUsers,$resultGroups,$resultHealthcares);

        //Obtem os root folders que podem ser vistos pelo usuário
        $searchQuery = $search;
        return view('shared/searchResults', compact('resultFolders', 
                                                    'resultFiles',
                                                    'resultUsers',
                                                    'resultGroups',
                                                    'resultHealthcares',
                                                    'searchFolder', 
                                                    'searchQuery') );
    }
}
