<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\File;
use App\Models\Folder;
use Symfony\Component\HttpKernel\HttpCache\Store;
use App\Document;

class FileController extends Controller
{

    public function delete($id) {
        $this->deleteFile($id);

        return response()->json(json_encode(['return' => true]), 200);
    }
}