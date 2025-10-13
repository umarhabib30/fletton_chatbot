<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Credential;
use Illuminate\Http\Request;

class CredentialController extends Controller
{
    public function index(){
        $credentials = Credential::find(1);
         $data = [
            'title' => 'Credentials',
            'active' => 'credentials',
            'credentials' => $credentials
        ];

        return view('admin.credentials.index', $data);
    }

    public function update(Request $request){
        $credentials = Credential::find(1);
        $credentials->update($request->all());
        return redirect()->back()->with('success', 'Credentials updated');
    }
}
