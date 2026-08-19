<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Apprenant;
use App\Models\Formation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class InscriptionController extends Controller
{
    /**
     * =========================================================
     * LISTE DES INSCRIPTIONS
     * =========================================================
     */
    public function index()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non connecté.'
            ], 401);
        }

        /*
         * ADMIN / GESTIONNAIRE
         */
        if (in_array($user->role, ['admin', 'gestionnaire'])) {

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
        $apprenant = Apprenant::where(
            'user_id',
            $user->id
        )->first();

        if (!$apprenant) {
            return response()->json([
                'message' => 'Dossier apprenant introuvable.'
            ], 404);
        }

        return response()->json(
            Inscription::with('formation')
                ->where('apprenant_id', $apprenant->id)
                ->latest()
                ->get()
        );
    }


    /**
     * =========================================================
     * INSCRIPTION PUBLIQUE
     * =========================================================
     */
    public function inscriptionPublique(Request $request)
    {
        /*
         * =====================================================
         * DATE LIMITE POUR AVOIR AU MOINS 11 ANS
         * =====================================================
         *
         * Exemple :
         * Aujourd'hui = 18/08/2026
         *
         * Date minimale acceptée :
         * 18/08/2015
         *
         * Une personne née le 18/08/2015 = 11 ans
         * Une personne née le 19/08/2015 = 10 ans
         */

        $dateLimite = now()
            ->subYears(11)
            ->toDateString();


        /*
         * =====================================================
         * VALIDATION
         * =====================================================
         */
        $request->validate([

            'nom' =>
                'required|string|max:255',

            'prenom' =>
                'required|string|max:255',

            'email' =>
                'required|email|max:255|unique:users,email',

            /*
             * Le mot de passe choisi par l'utilisateur
             */
            'password' =>
                'required|string|min:8|confirmed',

            'telephone' =>
                'required|string|max:30',

            /*
             * AU MOINS 11 ANS
             */
            'date_naissance' =>
                'required|date|before_or_equal:' . $dateLimite,

            'sexe' =>
                'required|in:Masculin,Feminin',

            'situation_matrimoniale' =>
                'required|in:Celibataire,Marie,Divorce,Veuf',

            'niveau_etude' =>
                'required|string|max:255',

            'niveau_informatique' =>
                'required|in:Debutant,Intermediaire,Avance',

            'adresse' =>
                'required|string|max:255',

            'formation_id' =>
                'required|exists:formations,id',

            'horaire' =>
                'required|string|max:100',
        ]);


        /*
         * =====================================================
         * FORMATION
         * =====================================================
         */
        $formation = Formation::findOrFail(
            $request->formation_id
        );


        /*
         * Vérifier si la formation est active
         */
        if (!$formation->is_active) {

            return response()->json([
                'message' =>
                    'Cette formation n’est plus disponible.'
            ], 409);
        }


        /*
         * =====================================================
         * VÉRIFIER LA CAPACITÉ
         * =====================================================
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


            if ($nombreInscrits >= $formation->capacite) {

                return response()->json([
                    'message' =>
                        'La capacité maximale de cette formation est atteinte.'
                ], 409);
            }
        }


        /*
         * =====================================================
         * CRÉATION DU COMPTE UTILISATEUR
         * =====================================================
         *
         * IMPORTANT :
         *
         * Le mot de passe n'est PLUS fixe.
         *
         * Le mot de passe choisi dans le formulaire est utilisé.
         */
        $nouveauUser = User::create([

            'nom' =>
                $request->nom,

            'prenom' =>
                $request->prenom,

            'email' =>
                $request->email,

            'telephone' =>
                $request->telephone,

            /*
             * MOT DE PASSE PERSONNEL
             */
            'password' =>
                Hash::make($request->password),

            'role' =>
                'apprenant',

            'is_active' =>
                true,
        ]);


        /*
         * =====================================================
         * CRÉATION DU DOSSIER APPRENANT
         * =====================================================
         */
        $apprenant = Apprenant::create([

            'created_by' =>
                $nouveauUser->id,

            'user_id' =>
                $nouveauUser->id,

            'matricule' =>
                $this->genererMatricule(),

            'date_naissance' =>
                $request->date_naissance,

            'sexe' =>
                $request->sexe,

            'situation_matrimoniale' =>
                $request->situation_matrimoniale,

            'niveau_informatique' =>
                $request->niveau_informatique,

            'adresse' =>
                $request->adresse,

            'telephone' =>
                $request->telephone,

            'email' =>
                $request->email,

            'niveau_etude' =>
                $request->niveau_etude,

            'fonction' =>
                $request->fonction,
        ]);


        /*
         * =====================================================
         * VÉRIFIER LE DOUBLON
         * =====================================================
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
         * =====================================================
         * CRÉER L'INSCRIPTION
         * =====================================================
         */
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


        /*
         * =====================================================
         * RÉPONSE
         * =====================================================
         */
        return response()->json([

            'success' =>
                true,

            'message' =>
                'Votre demande d’inscription a été envoyée avec succès.',

            'data' => [

                'inscription' =>
                    $inscription->load('formation'),

                'apprenant' =>
                    $apprenant,

            ],

        ], 201);
    }


    /**
     * =========================================================
     * GÉNÉRER MATRICULE
     * =========================================================
     */
    private function genererMatricule()
    {
        $dernier =
            Apprenant::latest('id')->first();


        if ($dernier && $dernier->matricule) {

            $numero =
                intval(
                    substr(
                        $dernier->matricule,
                        6
                    )
                ) + 1;

        } else {

            $numero = 1;
        }


        return 'CRE-DP' .
            str_pad(
                $numero,
                4,
                '0',
                STR_PAD_LEFT
            );
    }


    /**
     * =========================================================
     * INSCRIPTION APPRENANT CONNECTÉ
     * =========================================================
     */
    public function store(Request $request)
    {
        $request->validate([

            'formation_id' =>
                'required|exists:formations,id',

            'horaire' =>
                'required|string',

        ]);


        $user =
            Auth::guard('api')->user();


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


        if (!$formation->is_active) {

            return response()->json([
                'message' =>
                    'Cette formation n’est plus disponible.'
            ], 409);
        }


        $existe =
            Inscription::where(
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


            if ($nombreInscrits >= $formation->capacite) {

                return response()->json([
                    'message' =>
                        'La capacité maximale de cette formation est atteinte.'
                ], 409);
            }
        }


        $inscription =
            Inscription::create([

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
                $inscription->load('formation')

        ], 201);
    }


    /**
     * =========================================================
     * INSCRIPTION ADMIN
     * =========================================================
     */
    public function inscriptionAdmin(Request $request)
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


        $existe =
            Inscription::where(
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


            if ($nombreInscrits >= $formation->capacite) {

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
     * =========================================================
     * DETAIL
     * =========================================================
     */
    public function show(string $id)
    {
        $user =
            Auth::guard('api')->user();


        if (!$user) {

            return response()->json([
                'message' =>
                    'Utilisateur non connecté.'
            ], 401);
        }


        $inscription =
            Inscription::with([
                'apprenant.user',
                'formation',
                'createur'
            ])
            ->findOrFail($id);


        if (
            $user->role === 'apprenant' &&
            $inscription->apprenant->user_id != $user->id
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
     * =========================================================
     * UPDATE
     * =========================================================
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
     * =========================================================
     * SUPPRESSION
     * =========================================================
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