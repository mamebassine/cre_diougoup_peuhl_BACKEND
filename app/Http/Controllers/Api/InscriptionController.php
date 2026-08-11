<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Apprenant;
use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InscriptionController extends Controller
{
    /**
     * LISTE DES INSCRIPTIONS
     */
    public function index()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' =>
                    'Utilisateur non connecté.'
            ], 401);
        }

        /*
         * ADMIN / GESTIONNAIRE
         */
        if (
            in_array(
                $user->role,
                ['admin', 'gestionnaire']
            )
        ) {
            return response()->json(
                Inscription::with([
                    'apprenant.user',
                    'formation',
                    'createur'
                ])
                ->latest()
                ->get()
            );
        }

        /*
         * APPRENANT
         */
        $apprenant =
            Apprenant::where(
                'user_id',
                $user->id
            )->first();

        if (!$apprenant) {
            return response()->json([
                'message' =>
                    'Dossier apprenant introuvable.'
            ], 404);
        }

        return response()->json(
            Inscription::with('formation')
                ->where(
                    'apprenant_id',
                    $apprenant->id
                )
                ->latest()
                ->get()
        );
    }

    /**
     * INSCRIPTION PAR APPRENANT
     */
    public function store(Request $request)
    {
        $request->validate([

            'formation_id' =>
                'required|exists:formations,id',

            'horaire' =>
                'required|string',

        ]);

        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' =>
                    'Utilisateur non connecté.'
            ], 401);
        }

        $apprenant =
            Apprenant::where(
                'user_id',
                $user->id
            )->first();

        if (!$apprenant) {
            return response()->json([
                'message' =>
                    'Vous devez d’abord créer votre dossier apprenant.'
            ], 404);
        }

        $formation =
            Formation::findOrFail(
                $request->formation_id
            );

        /*
         * Vérifier si la formation est active.
         */
        if (!$formation->is_active) {
            return response()->json([
                'message' =>
                    'Cette formation n’est plus disponible.'
            ], 409);
        }

        /*
         * Vérifier le doublon.
         */
        $existe = Inscription::where(
            'apprenant_id',
            $apprenant->id
        )
        ->where(
            'formation_id',
            $formation->id
        )
        ->exists();

        if ($existe) {
            return response()->json([
                'message' =>
                    'Vous êtes déjà inscrit à cette formation.'
            ], 409);
        }

        /*
         * Vérifier la capacité.
         */
        if ($formation->capacite !== null) {

            $nombreInscrits =
                Inscription::where(
                    'formation_id',
                    $formation->id
                )
                ->where(
                    'statut',
                    'Valide'
                )
                ->count();

            if (
                $nombreInscrits >=
                $formation->capacite
            ) {
                return response()->json([
                    'message' =>
                        'La capacité maximale de cette formation est atteinte.'
                ], 409);
            }
        }

        $inscription = Inscription::create([

            'apprenant_id' =>
                $apprenant->id,

            'formation_id' =>
                $formation->id,

            'horaire' =>
                $request->horaire,

            'date_inscription' =>
                now(),

            'statut' =>
                'En attente',

            'etat_formation' =>
                'Non commencée',

            'created_by' =>
                null,

        ]);

        return response()->json([
            'message' =>
                'Demande envoyée avec succès.',

            'data' =>
                $inscription->load(
                    'formation'
                )
        ], 201);
    }

    /**
     * INSCRIPTION DIRECTE PAR ADMIN / GESTIONNAIRE
     */
    public function inscriptionAdmin(
        Request $request
    ) {
        $user =
            Auth::guard('api')->user();

        if (
            !$user ||
            !in_array(
                $user->role,
                ['admin', 'gestionnaire']
            )
        ) {
            return response()->json([
                'message' =>
                    'Accès refusé'
            ], 403);
        }

        $request->validate([

            'apprenant_id' =>
                'required|exists:apprenants,id',

            'formation_id' =>
                'required|exists:formations,id',

            'horaire' =>
                'required|string',

        ]);

        $formation =
            Formation::findOrFail(
                $request->formation_id
            );

        if (!$formation->is_active) {
            return response()->json([
                'message' =>
                    'Cette formation n’est plus disponible.'
            ], 409);
        }

        /*
         * Vérifier le doublon.
         */
        $existe = Inscription::where(
            'apprenant_id',
            $request->apprenant_id
        )
        ->where(
            'formation_id',
            $request->formation_id
        )
        ->exists();

        if ($existe) {
            return response()->json([
                'message' =>
                    'Cet apprenant est déjà inscrit.'
            ], 409);
        }

        /*
         * Vérifier la capacité.
         */
        if ($formation->capacite !== null) {

            $nombreInscrits =
                Inscription::where(
                    'formation_id',
                    $formation->id
                )
                ->where(
                    'statut',
                    'Valide'
                )
                ->count();

            if (
                $nombreInscrits >=
                $formation->capacite
            ) {
                return response()->json([
                    'message' =>
                        'La capacité maximale de cette formation est atteinte.'
                ], 409);
            }
        }

        $inscription =
            Inscription::create([

                'apprenant_id' =>
                    $request->apprenant_id,

                'formation_id' =>
                    $request->formation_id,

                'horaire' =>
                    $request->horaire,

                'date_inscription' =>
                    now(),

                'statut' =>
                    'Valide',

                'etat_formation' =>
                    'Non commencée',

                'created_by' =>
                    $user->id,

            ]);

        return response()->json([
            'message' =>
                'Apprenant inscrit par administration.',

            'data' =>
                $inscription->load([
                    'apprenant.user',
                    'formation',
                    'createur'
                ])
        ], 201);
    }

    /**
     * DETAIL INSCRIPTION
     */
    public function show(string $id)
    {
        $user =
            Auth::guard('api')->user();

        $inscription =
            Inscription::with([
                'apprenant.user',
                'formation',
                'createur'
            ])
            ->findOrFail($id);

        if (
            $user->role === 'apprenant' &&
            $inscription
                ->apprenant
                ->user_id != $user->id
        ) {
            return response()->json([
                'message' =>
                    'Accès refusé.'
            ], 403);
        }

        return response()->json(
            $inscription
        );
    }

    /**
     * VALIDATION / MODIFICATION
     */
    public function update(
        Request $request,
        string $id
    ) {
        $user =
            Auth::guard('api')->user();

        if (
            !$user ||
            !in_array(
                $user->role,
                ['admin', 'gestionnaire']
            )
        ) {
            return response()->json([
                'message' =>
                    'Accès refusé'
            ], 403);
        }

        $request->validate([

            'statut' =>
                'sometimes|in:En attente,Valide,Refuse',

            'etat_formation' =>
                'sometimes|in:Non commencée,En cours,Terminée,Abandonnée',

        ]);

        $inscription =
            Inscription::findOrFail($id);

        $data = [];

        if ($request->has('statut')) {

            $data['statut'] =
                $request->statut;
        }

        if ($request->has('etat_formation')) {

            $data['etat_formation'] =
                $request->etat_formation;
        }

        /*
         * On garde la trace de l'admin/
         * gestionnaire qui effectue la modification.
         */
        $data['created_by'] =
            $user->id;

        $inscription->update($data);

        return response()->json([
            'message' =>
                'Inscription mise à jour.',

            'data' =>
                $inscription->load([
                    'apprenant.user',
                    'formation',
                    'createur'
                ])
        ]);
    }

    /**
     * SUPPRESSION
     */
    public function destroy(string $id)
    {
        $user =
            Auth::guard('api')->user();

        if (
            !$user ||
            !in_array(
                $user->role,
                ['admin', 'gestionnaire']
            )
        ) {
            return response()->json([
                'message' =>
                    'Accès refusé'
            ], 403);
        }

        $inscription =
            Inscription::findOrFail($id);

        $inscription->delete();

        return response()->json([
            'message' =>
                'Inscription supprimée.'
        ]);
    }
}