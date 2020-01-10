<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\File;
use App\Models\FileDownload;
use App\Models\FilePermission;
use App\Models\FileVersion;
use App\Models\Folder;
use Symfony\Component\HttpKernel\HttpCache\Store;
use App\Document;
use DB, Auth;

use App\Group;
use App\Healthcare;
use App\Models\FolderPermission;
use App\User;

class FileController extends Controller
{

    public function newUploadAjax(Request $request) {
        $user = Auth::user();
        $folder_id = $request->parent_id;
        $key = $request->key;

        $file = $request->file('file');
        try{
            if ($file) {

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
                    'folder_id' => $folder_id,
                    'views' => 0,
                    'user_id' => $user->id,
                ];
                $newFile = File::create($newFile);

                $parentFolderPermission = FolderPermission::where('folder_id', $folder_id)->get()->all();

                foreach($parentFolderPermission as $permission) {
                    FilePermission::create(['file_id' => $newFile->id, 'user_id' => $permission->user_id, 'group_id' => $permission->group_id, 'healthcare_id' => $permission->healthcare_id]);
                }
                
                $firstFileVersion = [
                    'filename' => $profilefile,
                    'size' => $fileSize,
                    'absoluteSize' => $absoluteFileSize,
                    'user_id' => $user->id,
                    'file_id' => $newFile->id
                ];
                FileVersion::create($firstFileVersion);

                return response()->json(json_encode(['file' => $newFile, 'json' => $firstFileVersion]), '200');
            }
        }catch(Exception $ex){
            return response()->json(json_encode($ex->getMessage()), '500');
        }
    }

    public function newVersionUploadAjax(Request $request) {
        $user = Auth::user();
        $file = $request->file('file');
        try{
            if ($file) {

                $fileName = $file->getClientOriginalName();
                $absoluteFileSize = $file->getSize();

                //Solve file name and location
                $destinationPath = 'public/file/'; // upload path
                $profilefile = date('YmdHis') . "." . $file->getClientOriginalExtension();
                $file->move($destinationPath, $profilefile);

                //Solve file size
                $base = log($absoluteFileSize, 1024);
                $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');
                $fileSize = round(pow(1024, $base - floor($base)), 2) .' '. $suffixes[floor($base)];

                $path_parts = pathinfo($fileName);

                $file_id = $request->file_id;

                $firstFileVersion = [
                    'filename' => $profilefile,
                    'user_id' => $user->id,
                    'file_id' => $file_id,
                    'size' => $fileSize,
                    'absoluteSize' => $absoluteFileSize
                ];
                $lastVersion = FileVersion::create($firstFileVersion);

                return response()->json(json_encode(true), '200');
            }
            return response()->json(json_encode(false), '200');
        }catch(Exception $ex){
            return response()->json(json_encode(false), '200');
        }
    }

    public function upload(Request $request) {
        $folder_id = $request->folder_id;

        $user = Auth::user();
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
                'folder_id' => $folder_id,
                'views' => 0,
                'user_id' => $user->id,
            ];
            $newFile = File::create($newFile);

            $firstFileVersion = [
                'filename' => $profilefile,
                'size' => $fileSize,
                'absoluteSize' => $absoluteFileSize,
                'user_id' => $user->id,
                'file_id' => $newFile->id
            ];
            FileVersion::create($firstFileVersion);
        }

        return back();
    }

    public function removePermition(Request $request, $file_id){
        $file = File::find($file_id);
        $type = $request->type;
        if($type == 'user'){
            $file->userPermissions()->detach($request->id);
        } else if($type == 'group'){
            $file->groupPermissions()->detach($request->id);
        } else if($type == 'healthcare'){
            $file->healthcarePermissions()->detach($request->id);
        }

        return response()->json(true, '200');
    }

    public function download($id) {
        $targetFile = File::find($id);
        $targetFile->filename = $targetFile->latestVersion[0]->filename;

        $file = public_path().DIRECTORY_SEPARATOR."public".DIRECTORY_SEPARATOR."file".DIRECTORY_SEPARATOR.$targetFile->filename;
        $fileExt = pathinfo($targetFile->filename, PATHINFO_EXTENSION);
        $headers = array(
            'Content-Type: application/octet-stream',
        );

        $user = Auth::user();
        FileDownload::create(['file_id' => $id, 'user_id' => $user->id]);

        // dd($file, $fileExt, $targetFile, "TESTE:", );

        return response()->download($file, $targetFile->name.'.'.$fileExt, $headers);
    }
    public function downloadVersion($id){
        $targetVersion = FileVersion::find($id);
        $targetFile = File::find($targetVersion->file_id);
        $targetFile->filename = $targetVersion->filename;

        $file = public_path().DIRECTORY_SEPARATOR."public".DIRECTORY_SEPARATOR."file".DIRECTORY_SEPARATOR.$targetFile->filename;
        $fileExt = pathinfo($targetFile->filename, PATHINFO_EXTENSION);
        $headers = array(
            'Content-Type: application/'.$fileExt ,
        );

        $user = Auth::user();
        FileDownload::create(['file_id' => $id, 'user_id' => $user->id]);

        return response()->download($file, $targetFile->name.'.'.$fileExt, $headers);
    }

    public function getVersionsGroupedByDate($id){
        $dates = FileVersion::where('file_id', $id)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get(array(
                DB::raw('Date(created_at) as date')
            ));

        $versionsByDate = [];
        foreach($dates as $key => $date){
            $entries = FileVersion::where('file_id', $id)->whereDate('created_at', $date['date'])->orderBy('created_at', 'ASC')->get();
            foreach($entries as $key => $item){
                $file = $item->file;
                $file->ext = pathinfo($item->filename, PATHINFO_EXTENSION);
                $entries[$key]->file = $file;

                $user = $item->user;
                $entries[$key]->user = $user;
            }

            $group = [
                'date' => $date['date'],
                'versions' => $entries
            ];

            $versionsByDate[] = $group;
        }

        return response()->json(json_encode($versionsByDate), '200');
    }

    public function deleteVersion($id) {
        $fileVersion = FileVersion::find($id);
        unlink(public_path().DIRECTORY_SEPARATOR."public".DIRECTORY_SEPARATOR."file".DIRECTORY_SEPARATOR.$fileVersion->filename);
        $fileVersion->delete();

        return response()->json(json_encode(['return' => true]), 200);
    }
    public function delete($id) {
        $this->deleteFile($id);

        return response()->json(json_encode(['return' => true]), 200);
    }

    public function deleteFile($id){
        $targetFile = File::find($id);
        $targetFile->userPermissions()->detach();

        $fileVersions = FileVersion::where('file_id', $id)->get();
        foreach($fileVersions as $version){
            $path = public_path().DIRECTORY_SEPARATOR."public".DIRECTORY_SEPARATOR."file".DIRECTORY_SEPARATOR.$version->filename;
            if(file_exists($path))
                unlink($path);
            $version->delete();
        }

        $targetFile->delete();
    }

    public function rename(Request $request ,$id){
        $targetFile = File::find($id);
        $targetFile->name = $request->name;
        $targetFile->save();

        return back();
    }

    public function view(Request $request, $id){
        $targetFile = File::find($id);
        $targetFile->views = $targetFile->views + 1;
        $targetFile->save();

        return back();
    }

    public function details(Request $request, $id) {
        $targetFile = File::find($id);
        $latestVersion = $targetFile->latestVersion[0];
        $targetFile->filename = $latestVersion->filename;
        $targetFile->user = $latestVersion->user;

        $targetFile->imgURL = asset('public/file/'.$targetFile->filename);

        $ext = pathinfo($targetFile->filename, PATHINFO_EXTENSION);
        $imageFormats = ['png', 'jpg', 'bmp', 'gif', 'tif'];
        $videoFormats = ['webm', 'mkv', 'flv', 'vob', 'ogg', 'ogv', 'avi', 'wmv', 'rm', 'webm', 'yuv', 'rmvb', 'asf', 'amv', 'mp4', 'm4p', 'mpg', 'mp2', 'mpeg', 'mpe', 'mpv', 'm4v', '3gp', '3g2'];
        $documentFormats = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt'];

        $tipo = '?';
        if (in_array ($ext, $imageFormats)){
            $tipo = 'Imagem';
        } else if (in_array ($ext, $videoFormats)){
            $tipo = 'Video';
        } else if (in_array ($ext, $documentFormats)){
            $tipo = 'Documento';
        }

        $dates = FileDownload::where('file_id', $targetFile->id)
            ->groupBy('date')
            ->orderBy('date', 'DESC')
            ->get(array(
                DB::raw('Date(created_at) as date')
            ));

        $totalDownloads = 0;
        $downloadsByDate = [];
        foreach($dates as $key => $date) {

            $entries = FileDownload::where('file_id', $targetFile->id)->whereDate('created_at', $date['date'])->get();

            foreach($entries as $key => $download) {
                $download->username = $download->user->name;
                $download->filename = $download->file->latestVersion()->first()->filename;

                $targetFile->downloads['key'] = $download;
                $totalDownloads++;
            }

            $group = [
                'date' => $date['date'],
                'downloads' => $entries
            ];

            $downloadsByDate[] = $group;
        }

        $targetFile->totalDownloads = $totalDownloads;
        $targetFile->downloadsByDate = $downloadsByDate;

        $targetFile->tipo = $tipo;
        $targetFile->ext = $ext;

        return response()->json(json_encode($targetFile), '200');
    }
    public function sharedWithWhoInfo(Request $request, $file_id){
        $file = File::find($file_id);

        $users = $file->userPermissions()->distinct('file_id')->get();
        $groups = $file->groupPermissions()->distinct()->get();
        foreach($groups as $key => $item){
            $item->usersIn = count($item->users);
            $groups[$key] = $item;
        }
        $healthcares = $file->healthcarePermissions()->distinct()->get();
        foreach($healthcares as $key => $item){
            $item->usersIn = count($item->users);
            $healthcares[$key] = $item;
        }

        return response()->json(['users' => $users, 'groups' => $groups, 'healthcares' => $healthcares],200);
    }

    public function moveToFolder(Request $request, $file_id){
        $targetFile = File::find($file_id);

        $targetFolderID = $request->folder_id;
        $targetFile->folder_id = $targetFolderID;
        $targetFile->save();

        return back();
    }

    public function moveToFolderAjax(Request $request, $file_id){
        $targetFile = File::find($file_id);

        $targetFolderID = $request->parent_id;
        $targetFile->folder_id = $targetFolderID;
        $targetFile->save();

        return response()->json(true, 200);
    }

    /*
    *
    *   Shares a single file with an user, group or healthcare
    *
    */
    public function shareSingleFile(Request $request){
        $credentials = $request->all();

        switch ($credentials['type']){
            case 'group':
                foreach($credentials['ids'] as $id) {
                    $users = Group::find($id)->users()->get()->pluck('id');
                    $file = File::find($credentials['file_id'])->userPermissions()->attach($users, ['group_id'=> $id]);
                }
                break;
            case 'healthcare':
                foreach($credentials['ids'] as $id) {
                    $users = Healthcare::find($id)->users()->get()->pluck('id');
                    $file = File::find($credentials['file_id'])->userPermissions()->attach($users, ['healthcare_id'=> $id]);
                }
                break;
            default: //self
                foreach($credentials['ids'] as $id) {
                    $file = File::find($credentials['file_id'])->userPermissions()->attach($id);
                }
                break;
        }

        return response()->json(['Success'=>'folder shared'],200);
    }

    /*
    *
    *   Removes one file access permission for a every user, group or healthcare
    *
    */
    public function removeShareSingleFile(Request $request){
        $credentials = $request->all();

        switch ($credentials['type']){
            case 'group':

                $folder = File::find($credentials['folder_id'])->groupPermissions()->detach($credentials['group_id']);
                break;
            case 'healthcare':

                $folder = File::find($credentials['folder_id'])->groupPermissions()->detach($credentials['healthcare_id']);
                break;
            default: //self
                $folder = File::find($credentials['folder_id'])->userPermissions()
                                                                 ->wherePivot('group_id', null)
                                                                 ->wherePivot('healthcare_id', null)
                                                                 ->detach($credentials['user_id']);
                break;
        }

        return response()->json(['Success'=>'folder permission removed'],200);
    }

    public function getFileUploadData(Request $request){
        $files = FileVersion::orderBy('created_at','desc')->with('user')->take(20)->get();
        $files = collect($files)->unique('file_id')->all();

        $statistics=[];
        foreach($files as $file){
            $data_block =[
                'user_name'=>$file->user->name,
                'user_img'=>$file->user->imgURL,
                'totalSize'=>$file->absoluteSize,
                'name'=>$file->filename,
                'date'=>explode(' ',explode('T',$file->created_at)[0])[0],
            ];
            array_push($statistics, $data_block);
        }

        return response()->json($statistics,200);
    }

    public function getFullFilesUploadData(Request $request){
        $files = FileVersion::orderBy('created_at','desc')->with('user')->get();
        $files = collect($files)->unique('file_id')->all();

        $statistics=[];
        foreach($files as $file){
            $data_block =[
                'user_name'=>$file->user->name,
                'user_img'=>$file->user->imgURL,
                'totalSize'=>$file->absoluteSize,
                'name'=>$file->filename,
                'date'=>explode(' ',explode('T',$file->created_at)[0])[0],
            ];
            array_push($statistics, $data_block);
        }

        return response()->json($statistics,200);
    }
}
