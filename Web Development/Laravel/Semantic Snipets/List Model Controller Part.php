<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cliente;
use Validator;

class ClientesController extends Controller
{
	public function list(){
        $clientes = Cliente::orderBy('created_at', 'desc')->get();
        return view('pages/clientes', compact('clientes'));
    }
}
