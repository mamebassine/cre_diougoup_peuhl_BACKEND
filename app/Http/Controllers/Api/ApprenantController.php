<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprenantController extends Controller
{
    /**
     * LISTE DES APPRENANTS
     */
    public function index()
    {
        $user = Auth::guard('api')->user();

        // 👤 Apprenant voit seulement son propre dossier
        if ($user->role === 'apprenant') {
            return response()->json(
                Apprenant::with('user')
                    ->where('user_id', $user->id)
                    ->get()
            );
        }

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

        $request->validate([
            'matricule' => 'required|unique:apprenants,matricule',
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

        /**
         * 👤 CAS 1 : apprenant s’inscrit lui-même
         */
        if ($user->role === 'apprenant') {

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
                'message' => 'Apprenant inscrit avec succès',
                'data' => $apprenant
            ], 201);
        }

        /**
         * 👨‍💼 CAS 2 : admin / gestionnaire crée pour un utilisateur
         */
        if (in_array($user->role, ['admin', 'gestionnaire'])) {

            $request->validate([
                'user_id' => 'required|exists:users,id'
            ]);

            $apprenant = Apprenant::create([
                'user_id' => $request->user_id,
                'matricule' => $request->matricule,
                'date_naissance' => $request->date_naissance,
                'sexe' => $request->sexe,
                'situation_matrimoniale' => $request->situation_matrimoniale,
                'niveau_informatique' => $request->niveau_informatique,
                'statut' => 'Valide',
                'adresse' => $request->adresse,
                'telephone' => $request->telephone,
                'niveau_etude' => $request->niveau_etude,
                'module_choisi' => $request->module_choisi,
                'horaire_choisi' => $request->horaire_choisi,
            ]);

            return response()->json([
                'message' => 'Apprenant créé par gestionnaire/admin',
                'data' => $apprenant
            ], 201);
        }

        return response()->json([
            'message' => 'Accès refusé'
        ], 403);
    }

    /**
     * AFFICHER UN APPRENANT
     */
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
        $user = Auth::guard('api')->user();

        $apprenant = Apprenant::findOrFail($id);

        // 👤 apprenant modifie seulement son profil
        if ($user->role === 'apprenant' && $apprenant->user_id != $user->id) {
            return response()->json([
                'message' => 'Accès refusé'
            ], 403);
        }

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