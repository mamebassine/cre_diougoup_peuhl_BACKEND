<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
       $request->validate([
    'nom' => 'required|string|max:255',
    'prenom' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email',
    'telephone' => 'nullable|string|max:20',
    'password' => 'required|min:6|confirmed'
]);
        $user = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
            'role' => 'apprenant'
        ]);

        return response()->json([
            'message'=>'Utilisateur créé',
            'user'=>$user
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email','password');

if (!$token = Auth::guard('api')->attempt($credentials))        {
            return response()->json([
                'message'=>'Identifiants incorrects'
            ],401);
        }

        return $this->respondWithToken($token);
    }

    public function profile()
    {
        
return response()->json(Auth::guard('api')->user());    }

    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message'=>'Déconnexion réussie'
        ]);
    }

// protected function respondWithToken(string $token)
// {
//     return response()->json([
//         'access_token' => $token,
//         'token_type' => 'bearer',
//         'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
//         'user' => Auth::guard('api')->user()
//     ]);
// }

protected function respondWithToken(string $token)
{
    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => 60 * 60, // 1 heure (ou ta config JWT)
        'user' => Auth::guard('api')->user()
    ]);
}
}