<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ApprenantController extends Controller
{
    /**
     * LISTE DES APPRENANTS
     */
    public function index()
    {
        $user = Auth::guard('api')->user();

        if (!$user || !in_array($user->role, ['admin', 'gestionnaire'])) {
            return response()->json([
                'message' => 'Accès refusé'
            ], 403);
        }

        return response()->json(
            Apprenant::with([
                'user',
                'inscriptions.formation'
            ])
            ->latest()
            ->get()
        );
    }


    /**
     * CREATION APPRENANT
     */
    public function store(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Non authentifié'
            ], 401);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDATION DES INFORMATIONS APPRENANT
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'date_naissance' => 'required|date',

            'sexe' => 'required|in:Masculin,Feminin',

            'situation_matrimoniale' =>
                'required|in:Celibataire,Marie,Divorce,Veuf',

            'niveau_informatique' =>
                'required|in:Debutant,Intermediaire,Avance',

            'adresse' => 'required|string',

            'telephone' => 'required|string',

            'niveau_etude' => 'required|string',

            'fonction' => 'nullable|string',

            'photo' =>
                'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);


        /*
        |--------------------------------------------------------------------------
        | GESTION DE LA PHOTO
        |--------------------------------------------------------------------------
        */

        $photoPath = null;

        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')
                ->store('apprenants', 'public');
        }


        /*
        |--------------------------------------------------------------------------
        | GENERATION DU MATRICULE
        |--------------------------------------------------------------------------
        */

        $dernier = Apprenant::latest('id')->first();

        if ($dernier) {
            $numero = intval(
                substr($dernier->matricule, 6)
            ) + 1;
        } else {
            $numero = 1;
        }

        $matricule = 'CRE-DP' . str_pad(
            $numero,
            4,
            '0',
            STR_PAD_LEFT
        );


        /*
        |--------------------------------------------------------------------------
        | APPRENANT CONNECTÉ
        |--------------------------------------------------------------------------
        */

        if ($user->role === 'apprenant') {

            // Un utilisateur ne peut avoir qu'un seul dossier
            if (Apprenant::where('user_id', $user->id)->exists()) {
                return response()->json([
                    'message' => 'Vous avez déjà un dossier apprenant.'
                ], 409);
            }


            $apprenant = Apprenant::create([

                'created_by' => $user->id,

                'user_id' => $user->id,

                'matricule' => $matricule,

                'date_naissance' => $request->date_naissance,

                'sexe' => $request->sexe,

                'situation_matrimoniale' =>
                    $request->situation_matrimoniale,

                'niveau_informatique' =>
                    $request->niveau_informatique,

                'adresse' => $request->adresse,

                'telephone' => $request->telephone,

                'email' => $user->email,

                'niveau_etude' => $request->niveau_etude,

                'fonction' => $request->fonction,

                'photo' => $photoPath,
            ]);


            return response()->json([
                'message' => 'Dossier apprenant créé avec succès.',
                'data' => $apprenant->load('user')
            ], 201);
        }


        /*
        |--------------------------------------------------------------------------
        | ADMIN / GESTIONNAIRE
        |--------------------------------------------------------------------------
        */

        if (in_array($user->role, ['admin', 'gestionnaire'])) {

            /*
            |--------------------------------------------------------------------------
            | Informations du compte utilisateur
            |--------------------------------------------------------------------------
            */

            $request->validate([
                'nom' =>
                    'required|string|max:255',

                'prenom' =>
                    'required|string|max:255',

                'email' =>
                    'required|email|unique:users,email',

                'password' =>
                    'nullable|min:6'
            ]);


            /*
            |--------------------------------------------------------------------------
            | CREATION DU COMPTE UTILISATEUR
            |--------------------------------------------------------------------------
            */

            $nouveauUser = User::create([

                'nom' => $request->nom,

                'prenom' => $request->prenom,

                'email' => $request->email,

                'telephone' => $request->telephone,

                'password' => Hash::make(
                    $request->password ?? 'password123'
                ),

                'role' => 'apprenant',

                'is_active' => true,
            ]);


            /*
            |--------------------------------------------------------------------------
            | CREATION DU DOSSIER APPRENANT
            |--------------------------------------------------------------------------
            */

            $apprenant = Apprenant::create([

                'created_by' => $user->id,

                'user_id' => $nouveauUser->id,

                'matricule' => $matricule,

                'date_naissance' => $request->date_naissance,

                'sexe' => $request->sexe,

                'situation_matrimoniale' =>
                    $request->situation_matrimoniale,

                'niveau_informatique' =>
                    $request->niveau_informatique,

                'adresse' => $request->adresse,

                'telephone' => $request->telephone,

                'email' => $request->email,

                'niveau_etude' => $request->niveau_etude,

                'fonction' => $request->fonction,

                'photo' => $photoPath,
            ]);


            return response()->json([
                'message' => 'Apprenant créé avec succès.',
                'data' => $apprenant->load('user')
            ], 201);
        }


        return response()->json([
            'message' => 'Accès refusé'
        ], 403);
    }


    /**
     * MON PROFIL
     */
    public function monProfil()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non connecté.'
            ], 401);
        }

        return response()->json([

            'user' => $user,

            'apprenant' => Apprenant::with([
                'inscriptions.formation'
            ])
            ->where('user_id', $user->id)
            ->first()
        ]);
    }


    /**
     * DETAIL APPRENANT
     */
    public function show(string $id)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non connecté.'
            ], 401);
        }


        $apprenant = Apprenant::with([

            'user',

            'inscriptions.formation',

            // NE PAS SUPPRIMER
            'diplomes',

            // NE PAS SUPPRIMER
            'boiteIdees'

        ])
        ->findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | Un apprenant peut uniquement voir son propre dossier
        |--------------------------------------------------------------------------
        */

        if (
            $user->role === 'apprenant'
            &&
            $apprenant->user_id != $user->id
        ) {
            return response()->json([
                'message' => 'Accès refusé'
            ], 403);
        }


        return response()->json($apprenant);
    }


    /**
     * MODIFICATION APPRENANT
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::guard('api')->user();

        if (
            !$user ||
            !in_array($user->role, ['admin', 'gestionnaire'])
        ) {
            return response()->json([
                'message' => 'Accès refusé'
            ], 403);
        }


        $apprenant = Apprenant::findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $request->validate([

            'date_naissance' =>
                'sometimes|date',

            'sexe' =>
                'sometimes|in:Masculin,Feminin',

            'situation_matrimoniale' =>
                'sometimes|in:Celibataire,Marie,Divorce,Veuf',

            'niveau_informatique' =>
                'sometimes|in:Debutant,Intermediaire,Avance',

            'adresse' =>
                'sometimes|string',

            'telephone' =>
                'sometimes|string',

            'email' =>
                'sometimes|nullable|email',

            'niveau_etude' =>
                'sometimes|string',

            'fonction' =>
                'sometimes|nullable|string',

            'photo' =>
                'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);


        /*
        |--------------------------------------------------------------------------
        | PHOTO
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('photo')) {

            if ($apprenant->photo) {
                Storage::disk('public')
                    ->delete($apprenant->photo);
            }

            $apprenant->photo =
                $request->file('photo')
                    ->store('apprenants', 'public');
        }


        /*
        |--------------------------------------------------------------------------
        | MISE À JOUR
        |--------------------------------------------------------------------------
        */

        $apprenant->update(
            $request->except([
                'photo',
                'matricule',
                'user_id',
                'created_by'
            ])
        );


        $apprenant->save();


        return response()->json([
            'message' => 'Apprenant modifié avec succès.',
            'data' => $apprenant->load('user')
        ]);
    }


    /**
     * SUPPRESSION APPRENANT
     */
    public function destroy(string $id)
    {
        $user = Auth::guard('api')->user();

        if (
            !$user ||
            !in_array($user->role, ['admin', 'gestionnaire'])
        ) {
            return response()->json([
                'message' => 'Accès refusé'
            ], 403);
        }


        $apprenant = Apprenant::findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | SUPPRESSION DE LA PHOTO
        |--------------------------------------------------------------------------
        */

        if ($apprenant->photo) {

            Storage::disk('public')
                ->delete($apprenant->photo);
        }


        /*
        |--------------------------------------------------------------------------
        | SUPPRESSION DU DOSSIER
        |--------------------------------------------------------------------------
        */

        $apprenant->delete();


        return response()->json([
            'message' => 'Apprenant supprimé avec succès.'
        ]);
    }
}

