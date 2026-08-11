<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{


    /**
     * CREATION COMPTE UTILISATEUR
     */
    public function register(Request $request)
    {

        $request->validate([


            'nom' =>
            'required|string|max:255',


            'prenom' =>
            'required|string|max:255',


            'email' =>
            'required|email|unique:users,email',


            'telephone' =>
            'nullable|string|max:20',


            'password' =>
            'required|min:6|confirmed',


            'photo' =>
            'nullable|image|mimes:jpg,jpeg,png|max:2048',


        ]);





        // Gestion photo

        $photoPath = null;


        if($request->hasFile('photo'))
        {

            $photoPath = $request->file('photo')
                ->store('users','public');

        }







        // Création utilisateur

        $user = User::create([


            'nom'=>$request->nom,


            'prenom'=>$request->prenom,


            'email'=>$request->email,


            'telephone'=>$request->telephone,


            'password'=>Hash::make(
                $request->password
            ),


            'role'=>'apprenant',


            'photo'=>$photoPath,


            'is_active'=>true,


        ]);







        return response()->json([


            'message'=>'Compte créé avec succès.',


            'user'=>$user


        ],201);


    }









    /**
     * CONNEXION
     */
    public function login(Request $request)
    {


        $request->validate([


            'email'=>'required|email',


            'password'=>'required|string',


        ]);





        $credentials = $request->only(
            'email',
            'password'
        );







        $user = User::where(
            'email',
            $request->email
        )->first();





        if(!$user || !$user->is_active)
        {

            return response()->json([


                'message'=>'Compte désactivé ou inexistant.'


            ],403);

        }







        if(!$token = Auth::guard('api')->attempt($credentials))
        {


            return response()->json([


                'message'=>'Email ou mot de passe incorrect.'


            ],401);


        }







        return $this->respondWithToken($token);


    }









    /**
     * PROFIL CONNECTE
     */
    public function profile()
    {


        $user = Auth::guard('api')->user();




        if(!$user)
        {

            return response()->json([

                'message'=>'Utilisateur non connecté.'

            ],401);

        }







        return response()->json([


            'user'=>$user


        ]);

    }









    /**
     * DECONNEXION
     */
    public function logout()
    {


        Auth::guard('api')->logout();




        return response()->json([


            'message'=>'Déconnexion réussie.'


        ]);

    }









    /**
     * RETOUR TOKEN JWT
     */
    protected function respondWithToken(?string $token)
    {


        return response()->json([


            'access_token'=>$token,


            'token_type'=>'bearer',


            'expires_in'=>60 * 60,


            'user'=>Auth::guard('api')->user()


        ]);


    }


}