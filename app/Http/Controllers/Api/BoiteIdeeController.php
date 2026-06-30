<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoiteIdee;
use App\Models\Apprenant;
use Illuminate\Http\Request;

class BoiteIdeeController extends Controller
{
    /**
     * Liste des idées
     * - Admin et gestionnaire : toutes les idées
     * - Apprenant : uniquement ses idées
     */
    public function index()
    {
        $user = auth()->guard('api')->user();

        if (in_array($user->role, ['admin', 'gestionnaire'])) {

            return response()->json(
                BoiteIdee::with('apprenant')->latest()->get()
            );
        }

        $apprenant = Apprenant::where('user_id', $user->id)->first();

        return response()->json(
            BoiteIdee::with('apprenant')
                ->where('apprenant_id', $apprenant->id)
                ->latest()
                ->get()
        );
    }

    /**
     * Envoyer un message
     * Apprenant uniquement
     */
    public function store(Request $request)
    {
        $request->validate([
            'type_message' => 'required|in:Suggestion,Reclamation,Question,Demande',
            'objet' => 'required|string|max:255',
            'message' => 'required|string'
        ]);

        $user = auth()->guard('api')->user();

        if ($user->role != 'apprenant') {
            return response()->json([
                'message' => 'Seul un apprenant peut envoyer un message.'
            ], 403);
        }

        $apprenant = Apprenant::where('user_id', $user->id)->first();

        if (!$apprenant) {
            return response()->json([
                'message' => 'Apprenant introuvable.'
            ], 404);
        }

        $idee = BoiteIdee::create([
            'apprenant_id' => $apprenant->id,
            'type_message' => $request->type_message,
            'objet' => $request->objet,
            'message' => $request->message,
            'is_read' => false,
            'statut' => 'Nouveau',
            'reponse' => null,
            'date_reponse' => null
        ]);

        return response()->json([
            'message' => 'Message envoyé avec succès.',
            'data' => $idee
        ], 201);
    }

    /**
     * Afficher une idée
     */
    public function show(string $id)
    {
        $user = auth()->guard('api')->user();

        $idee = BoiteIdee::with('apprenant')->findOrFail($id);

        if ($user->role == 'apprenant') {

            $apprenant = Apprenant::where('user_id', $user->id)->first();

            if (!$apprenant || $idee->apprenant_id != $apprenant->id) {

                return response()->json([
                    'message' => 'Accès refusé.'
                ], 403);
            }
        }

        return response()->json($idee);
    }

    /**
     * Modifier une idée
     */
        public function update(Request $request, string $id)
    {
        $user = auth()->guard('api')->user();

        $idee = BoiteIdee::findOrFail($id);

        /**
         * Cas de l'apprenant
         */
        if ($user->role == 'apprenant') {

            $apprenant = Apprenant::where('user_id', $user->id)->first();

            if (!$apprenant || $idee->apprenant_id != $apprenant->id) {

                return response()->json([
                    'message' => 'Accès refusé.'
                ], 403);
            }

            // Impossible de modifier un message déjà traité
            if ($idee->statut == 'Traite') {

                return response()->json([
                    'message' => 'Ce message a déjà été traité.'
                ], 403);
            }

            $request->validate([
                'type_message' => 'required|in:Suggestion,Reclamation,Question,Demande',
                'objet' => 'required|string|max:255',
                'message' => 'required|string'
            ]);

            $idee->update([
                'type_message' => $request->type_message,
                'objet' => $request->objet,
                'message' => $request->message
            ]);
        }

        /**
         * Cas Admin / Gestionnaire
         */
        elseif (in_array($user->role, ['admin', 'gestionnaire'])) {

            $request->validate([
                'statut' => 'required|in:Nouveau,En cours,Traite',
                'reponse' => 'nullable|string',
                'is_read' => 'sometimes|boolean'
            ]);

            $idee->update([
                'statut' => $request->statut,
                'reponse' => $request->reponse,
                'is_read' => $request->input('is_read', true),
                'date_reponse' => $request->reponse ? now() : null,
            ]);
        }

        return response()->json([
            'message' => 'Message mis à jour avec succès.',
            'data' => $idee
        ]);
    }

    /**
     * Supprimer une idée
     */
    public function destroy(string $id)
    {
        $user = auth()->guard('api')->user();

        $idee = BoiteIdee::findOrFail($id);

        // Cas de l'apprenant
        if ($user->role == 'apprenant') {

            $apprenant = Apprenant::where('user_id', $user->id)->first();

            if (!$apprenant || $idee->apprenant_id != $apprenant->id) {

                return response()->json([
                    'message' => 'Accès refusé.'
                ], 403);
            }

            // Impossible de supprimer un message déjà traité
            if ($idee->statut == 'Traite') {

                return response()->json([
                    'message' => 'Impossible de supprimer un message déjà traité.'
                ], 403);
            }
        }

        // Admin et gestionnaire peuvent supprimer tous les messages

        $idee->delete();

        return response()->json([
            'message' => 'Message supprimé avec succès.'
        ]);
    }
}