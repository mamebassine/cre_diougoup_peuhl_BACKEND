<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ApprenantController extends Controller
{
    /**
     * LISTE DES APPRENANTS
     */
    public function index()
    {
        $user = Auth::guard('api')->user();

        // // 👤 Apprenant voit seulement son propre dossier
        // if ($user->role === 'apprenant') {
        //     return response()->json(
        //         Apprenant::with('user')
        //             ->where('user_id', $user->id)
        //             ->get()
        //     );
        // }

        // 👨‍💼 Admin + gestionnaire voient tout
        return response()->json(
            Apprenant::with('user')->latest()->get()
        );
    }


    
/**
 * CRÉATION APPRENANT
 */
public function store(Request $request)
{
    $user = Auth::guard('api')->user();

    if (!$user) {
        return response()->json([
            'message' => 'Unauthenticated'
        ], 401);
    }

    // Vérification personnalisée du matricule
    if (Apprenant::where('matricule', $request->matricule)->exists()) {
        return response()->json([
            'success' => false,
            'message' => 'Cet apprenant existe déjà.'
        ], 409);
    }

    // Validation commune
    $request->validate([
        'matricule' => 'required',
        'date_naissance' => 'required|date',
        'sexe' => 'required|in:Masculin,Feminin',
        'situation_matrimoniale' => 'required|in:Celibataire,Marie,Divorce,Veuf',
        'niveau_informatique' => 'required|in:Debutant,Intermediaire,Avance',
        'adresse' => 'required',
        'telephone' => 'required',
        'niveau_etude' => 'required',
        'module_choisi' => 'required',
        'horaire_choisi' => 'required',
    ]);

    /*
    |---------------------------------
    | CAS APPRENANT
    |---------------------------------
    */
    if ($user->role === 'apprenant') {

        if (Apprenant::where('user_id', $user->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous êtes déjà inscrit.'
            ], 409);
        }

        $apprenant = Apprenant::create([
            'user_id' => $user->id,
            'matricule' => $request->matricule,
            'date_naissance' => $request->date_naissance,
            'sexe' => $request->sexe,
            'situation_matrimoniale' => $request->situation_matrimoniale,
            'niveau_informatique' => $request->niveau_informatique,
            'statut' => 'En attente',
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'niveau_etude' => $request->niveau_etude,
            'module_choisi' => $request->module_choisi,
            'horaire_choisi' => $request->horaire_choisi,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Apprenant créé avec succès',
            'data' => $apprenant
        ], 201);
    }

    /*
    |---------------------------------
    | CAS ADMIN / GESTIONNAIRE
    |---------------------------------
    */
    if (in_array($user->role, ['admin', 'gestionnaire'])) {

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'nullable|min:6',
        ]);

        $nouvelUtilisateur = User::where('email', $request->email)->first();

        if (!$nouvelUtilisateur) {
            $nouvelUtilisateur = User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'password' => Hash::make($request->password ?? 'password123'),
                'role' => 'apprenant',
                'is_active' => true,
            ]);
        }

        if (Apprenant::where('user_id', $nouvelUtilisateur->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Déjà inscrit'
            ], 409);
        }

        $apprenant = Apprenant::create([
            'user_id' => $nouvelUtilisateur->id,
            'matricule' => $request->matricule,
            'date_naissance' => $request->date_naissance,
            'sexe' => $request->sexe,
            'situation_matrimoniale' => $request->situation_matrimoniale,
            'niveau_informatique' => $request->niveau_informatique,
            'statut' => 'Valide',
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'niveau_etude' => $request->niveau_etude,
            'fonction' => $request->fonction,
            'module_choisi' => $request->module_choisi,
            'horaire_choisi' => $request->horaire_choisi,
            'date_inscription' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Apprenant créé avec succès',
            'data' => $apprenant
        ], 201);
    }

    return response()->json([
        'success' => false,
        'message' => 'Accès refusé'
    ], 403);
}

// public function monProfil()
// {
//     $user = Auth::guard('api')->user();

//     return Apprenant::with('user')
//         ->where('user_id', $user->id)
//         ->first();


//             if (!$apprenant) {
//         return response()->json([
//             'message' => 'Aucun profil apprenant trouvé.'
//         ], 404);
//     }

//     return response()->json([
//         'message' => 'Profil récupéré avec succès.',
//         'data' => $apprenant
//     ]);
// }



    /**
     * AFFICHER UN APPRENANT
     */
    
    public function monProfil()
{
    $user = Auth::guard('api')->user();

    $apprenant = Apprenant::with('user')
        ->where('user_id', $user->id)
        ->first();

    return response()->json([
        'user' => $user,
        'apprenant' => $apprenant
    ]);
}
    public function show(string $id)
    {
        $user = Auth::guard('api')->user();

        $apprenant = Apprenant::with([
            'user',
            'diplomes',
            'boiteIdees'
        ])->findOrFail($id);

        // 👤 sécurité apprenant
        if ($user->role === 'apprenant' && $apprenant->user_id != $user->id) {
            return response()->json([
                'message' => 'Accès refusé'
            ], 403);
        }

        return response()->json($apprenant);
    }

    /**
     * MODIFIER APPRENANT
     */
    
    public function update(Request $request, string $id)
{
    $apprenant = Apprenant::findOrFail($id);

    $request->validate([
        'matricule' => 'sometimes|unique:apprenants,matricule,' . $apprenant->id,
        'date_naissance' => 'sometimes|date',
        'sexe' => 'sometimes|in:Masculin,Feminin',
        'situation_matrimoniale' => 'sometimes|in:Celibataire,Marie,Divorce,Veuf',
        'niveau_informatique' => 'sometimes|in:Debutant,Intermediaire,Avance',
        'statut' => 'sometimes|in:En attente,Valide,Refuse',
        'adresse' => 'sometimes',
        'telephone' => 'sometimes',
        'niveau_etude' => 'sometimes',
        'module_choisi' => 'sometimes',
        'horaire_choisi' => 'sometimes',
    ]);

    $apprenant->update($request->only([
        'matricule',
        'date_naissance',
        'sexe',
        'situation_matrimoniale',
        'niveau_informatique',
        'statut',
        'adresse',
        'telephone',
        'niveau_etude',
        'module_choisi',
        'horaire_choisi',
    ]));

    return response()->json([
        'message' => 'Apprenant modifié avec succès',
        'data' => $apprenant
    ]);
}

    public function updateMonProfil(Request $request)
{
    $user = Auth::guard('api')->user();

    $apprenant = Apprenant::where('user_id', $user->id)->firstOrFail();

    $request->validate([
        'adresse' => 'sometimes',
        'telephone' => 'sometimes',
        'niveau_etude' => 'sometimes',
        'module_choisi' => 'sometimes',
        'horaire_choisi' => 'sometimes',
    ]);

    $apprenant->update($request->only([
        'adresse',
        'telephone',
        'niveau_etude',
        'module_choisi',
        'horaire_choisi',
    ]));

    return response()->json([
        'message' => 'Profil mis à jour avec succès',
        'data' => $apprenant
    ]);
}

    /**
     * SUPPRESSION (admin + gestionnaire)
     */
    public function destroy(string $id)
    {
        $user = Auth::guard('api')->user();

        if (!in_array($user->role, ['admin', 'gestionnaire'])) {
            return response()->json([
                'message' => 'Accès refusé'
            ], 403);
        }

        $apprenant = Apprenant::findOrFail($id);
        $apprenant->delete();

        return response()->json([
            'message' => 'Apprenant supprimé avec succès'
        ]);
    }
}