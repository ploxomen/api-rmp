<?php

namespace App\Http\Controllers;

use App\Models\Roles;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function userRestrict($user,$url) {
        $role = $user->roles()->where('active',1)->first();
        $redirectRestrict = '/intranet/home';
        if(empty($role)){
            return $redirectRestrict;
        }
        if(empty($role->modules()->where('module_url',$url)->first())){
            return $redirectRestrict;
        }
        return null;
    }
    public function changeRole(Request $request) {
        $idUser = $request->user()->id;
        $usuarioRol = User::find($idUser);
        $usuarioRol->roles()->update(['active' => 0]);
        Roles::find($request->role)->users()->where('user',$idUser)->update(['active' => 1]);
        return response()->json([
            'redirect' => '/intranet/home',
            'error' => false,
            'message' => 'Usuario autenticado', 
            
        ]);
    }
    public function login(Request $request)
    {
        if(!Auth::attempt(['user_email' => $request->username,'password' => $request->password],$request->has('rememberme'))){
            return response()->json([
                'authenticate' => false,
                'error' => false,
                'message' => 'El usuario y/o la contraseña son incorrectos'
            ]);
        }
        $user = User::select('id','user_name','user_last_name')->where('user_email',$request->username)->firstOrFail();
         $token = $user->createToken('auth_token',['*'],now()->addDays($request->has('rememberme') ? 7 : 3))->plainTextToken;
        return response()->json([
            'authenticate' => true,
            'error' => false,
            'message' => 'Usuario autenticado', 
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ]
        ]);
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:250',
            'last_name' => 'required|string|max:250',
            'email' => 'required|string|max:250|unique:users,user_email',
            'password' => 'required|string|max:255'
        ]);
        if($validator->fails()){
            return response()->json(['error' => true, 'message'=>'Complete los datos requeridos','data' => $validator->errors()]);
        }
        $user = User::create([
            'user_type_document' => $request->type_document,
            'user_number_document' => $request->number_document,
            'user_name' => $request->name,
            'user_last_name' => $request->last_name,
            'user_email' => $request->email,
            'password' => Hash::make($request->password),
            'user_phone' => $request->phone,
            'user_cell_phone' => $request->cell_phone,
            'user_birthdate' => $request->birthdate,
            'user_status' => 1
        ]);
        $token = $user->createToken('auth_token',['*'],now()->addDays(3))->plainTextToken;
        return response()->json(['error' => false, 'message' => 'Usuario creado correctamente', 'data' => $user, 'access_token' => $token, 'token_type' => 'Berer']);
    }
    public function resetPassword(Request $request) {
        $validator = Validator::make($request->all(),[
            'password' => 'required|string|max:250',
        ]);
        if($validator->fails()){
            return response()->json(['error' => true, 'message'=>'Complete los datos requeridos','data' => $validator->errors()]);
        }
        User::find($request->user)->update(['user_status' => 2,'password' => Hash::make($request->password)]);
        $redirect = (new AuthController)->userRestrict($request->user(),'/users');
        return response()->json([
            'redirect' => $redirect,
            'error' => false,
            'message' => 'Se cambió la contraseña del usuario'
        ]);
    }
    public function logout() {
        auth()->user()->tokens()->delete();
        return response()->json([
            'error' => false,
            'message' => 'Se cerró la sesión correctamente'
        ]);
    }
    
}
