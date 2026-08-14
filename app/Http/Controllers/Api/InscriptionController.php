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
                ->where(
                    'apprenant_id',
                    $apprenant->id
                )
                ->latest()
                ->get()
        );
    }


    /**
     * =========================================================
     * INSCRIPTION PUBLIQUE À UNE FORMATION
     * =========================================================
     *
     * Cette méthode permet à une personne qui vient du site
     * public de :
     *
     * 1. remplir ses informations personnelles
     * 2. créer automatiquement son compte
     * 3. créer son dossier apprenant
     * 4. s'inscrire à la formation choisie
     *
     * Elle ne nécessite PAS d'être connecté.
     */
    public function inscriptionPublique(Request $request)
    {
        /*
         * VALIDATION DES DONNÉES
         */
        $request->validate([

            'nom' =>
                'required|string|max:255',

            'prenom' =>
                'required|string|max:255',

            'email' =>
                'required|email|max:255',

            'telephone' =>
                'required|string|max:30',

            'date_naissance' =>
                'required|date',

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
         * VÉRIFIER LA FORMATION
         */
        $formation = Formation::findOrFail(
            $request->formation_id
        );


        /*
         * VÉRIFIER SI LA FORMATION EST ACTIVE
         */
        if (!$formation->is_active) {

            return response()->json([
                'message' =>
                    'Cette formation n’est plus disponible.'
            ], 409);
        }


        /*
         * VÉRIFIER SI L'EMAIL EXISTE DÉJÀ
         */
        $userExistant = User::where(
            'email',
            $request->email
        )->first();


        /*
         * SI LE COMPTE EXISTE DÉJÀ
         */
        if ($userExistant) {

            /*
             * On cherche son dossier apprenant
             */
            $apprenantExistant = Apprenant::where(
                'user_id',
                $userExistant->id
            )->first();


            /*
             * Si le compte existe mais pas le dossier
             * on peut créer le dossier.
             */
            if (!$apprenantExistant) {

                $apprenantExistant = Apprenant::create([

                    'created_by' =>
                        $userExistant->id,

                    'user_id' =>
                        $userExistant->id,

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
            }

            $apprenant = $apprenantExistant;

        } else {

            /*
             * =================================================
             * CRÉATION DU COMPTE UTILISATEUR
             * =================================================
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
                 * Mot de passe temporaire.
                 *
                 * L'utilisateur pourra ensuite le modifier
                 * depuis son espace.
                 */
                'password' =>
                    Hash::make('password123'),

                'role' =>
                    'apprenant',

                'is_active' =>
                    true,

            ]);


            /*
             * =================================================
             * CRÉATION DU DOSSIER APPRENANT
             * =================================================
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
        }


        /*
         * =====================================================
         * VÉRIFIER SI L'APPRENANT EST DÉJÀ INSCRIT À CETTE
         * FORMATION
         * =====================================================
         *
         * Important :
         *
         * Un apprenant peut faire plusieurs formations.
         *
         * Exemple :
         *
         * Formation 1 → déjà inscrite
         * Formation 2 → elle peut s'inscrire
         * Formation 3 → elle peut s'inscrire
         *
         * Ce qui est interdit est seulement le doublon
         * sur LA MÊME formation.
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

            /*
             * Inscription faite depuis le site public,
             * donc aucun admin/gestionnaire créateur.
             */
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
     * GÉNÉRER UN MATRICULE
     * =========================================================
     */
    private function genererMatricule()
    {
        $dernier =
            Apprenant::latest('id')->first();


        if ($dernier && $dernier->matricule) {

            /*
             * Exemple :
             *
             * CRE-DP0001
             *
             * On récupère 0001
             */
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
     * INSCRIPTION PAR APPRENANT CONNECTÉ
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
         *
         * L'apprenant peut faire plusieurs formations.
         */
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
     * =========================================================
     * INSCRIPTION DIRECTE PAR ADMIN / GESTIONNAIRE
     * =========================================================
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
     * =========================================================
     * DETAIL INSCRIPTION
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
     * =========================================================
     * VALIDATION / MODIFICATION
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


        /*
         * On garde la trace de l'admin /
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