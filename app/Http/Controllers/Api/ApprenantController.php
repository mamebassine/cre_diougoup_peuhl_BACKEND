<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApprenantController extends Controller
{
    /**
     * =========================================================
     * LISTE DES APPRENANTS
     * =========================================================
     */
    public function index()
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
     * =========================================================
     * CREATION APPRENANT
     * =========================================================
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

            'numero_cni' => [
                'nullable',
                'string',
                'max:50',
                'unique:apprenants,numero_cni'
            ],

            'date_naissance' => [
                'required',
                'date'
            ],

            'sexe' => [
                'required',
                'in:Masculin,Feminin'
            ],

            'situation_matrimoniale' => [
                'required',
                'in:Celibataire,Marie,Divorce,Veuf'
            ],

            'niveau_informatique' => [
                'required',
                'in:Debutant,Intermediaire,Avance'
            ],

            'adresse' => [
                'required',
                'string'
            ],

            'telephone' => [
                'required',
                'string'
            ],

            'niveau_etude' => [
                'required',
                'string'
            ],

            'fonction' => [
                'nullable',
                'string'
            ],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],

            /*
            |--------------------------------------------------------------------------
            | SIGNATURE
            |--------------------------------------------------------------------------
            | La signature sera envoyée depuis le canvas
            | sous forme Base64.
            |--------------------------------------------------------------------------
            */

            'signature' => [
                'nullable',
                'string'
            ],
        ]);


        /*
        |--------------------------------------------------------------------------
        | GESTION DE LA PHOTO
        |--------------------------------------------------------------------------
        */

        $photoPath = null;

        if ($request->hasFile('photo')) {

            $photoPath = $request
                ->file('photo')
                ->store(
                    'apprenants',
                    'public'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | GENERATION DU MATRICULE
        |--------------------------------------------------------------------------
        */

        $dernier = Apprenant::latest('id')->first();

        if ($dernier) {

            $numero = intval(
                substr(
                    $dernier->matricule,
                    6
                )
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

            if (
                Apprenant::where(
                    'user_id',
                    $user->id
                )->exists()
            ) {

                return response()->json([
                    'message' =>
                        'Vous avez déjà un dossier apprenant.'
                ], 409);
            }


            $apprenant = Apprenant::create([

                'created_by' =>
                    $user->id,

                'user_id' =>
                    $user->id,

                'matricule' =>
                    $matricule,

                'numero_cni' =>
                    $request->numero_cni,

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

                'photo' =>
                    $photoPath,

                'signature' =>
                    $request->signature,
            ]);


            return response()->json([

                'message' =>
                    'Dossier apprenant créé avec succès.',

                'data' =>
                    $apprenant->load('user')

            ], 201);
        }


        /*
        |--------------------------------------------------------------------------
        | ADMIN / GESTIONNAIRE
        |--------------------------------------------------------------------------
        */

        if (
            in_array(
                $user->role,
                ['admin', 'gestionnaire']
            )
        ) {

            /*
            |--------------------------------------------------------------------------
            | VALIDATION DU COMPTE UTILISATEUR
            |--------------------------------------------------------------------------
            */

            $request->validate([

                'nom' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'prenom' => [
                    'required',
                    'string',
                    'max:255'
                ],

                'email' => [
                    'required',
                    'email',
                    'unique:users,email'
                ],

                'password' => [
                    'nullable',
                    'min:6'
                ]

            ]);


            /*
            |--------------------------------------------------------------------------
            | CREATION DU COMPTE UTILISATEUR
            |--------------------------------------------------------------------------
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

                'password' =>
                    Hash::make(
                        $request->password
                        ?? 'password123'
                    ),

                'role' =>
                    'apprenant',

                'is_active' =>
                    true,
            ]);


            /*
            |--------------------------------------------------------------------------
            | CREATION DU DOSSIER APPRENANT
            |--------------------------------------------------------------------------
            */

            $apprenant = Apprenant::create([

                'created_by' =>
                    $user->id,

                'user_id' =>
                    $nouveauUser->id,

                'matricule' =>
                    $matricule,

                'numero_cni' =>
                    $request->numero_cni,

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

                'photo' =>
                    $photoPath,

                'signature' =>
                    $request->signature,
            ]);


            return response()->json([

                'message' =>
                    'Apprenant créé avec succès.',

                'data' =>
                    $apprenant->load('user')

            ], 201);
        }


        return response()->json([
            'message' => 'Accès refusé'
        ], 403);
    }


    /**
     * =========================================================
     * MON PROFIL
     * =========================================================
     */
    public function monProfil()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {

            return response()->json([
                'message' =>
                    'Utilisateur non connecté.'
            ], 401);
        }


        return response()->json([

            'user' =>
                $user,

            'apprenant' =>
                Apprenant::with([
                    'inscriptions.formation'
                ])
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->first()

        ]);
    }


    /**
     * =========================================================
     * DETAIL APPRENANT
     * =========================================================
     */
    public function show(string $id)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {

            return response()->json([
                'message' =>
                    'Utilisateur non connecté.'
            ], 401);
        }


        $apprenant = Apprenant::with([

            'user',

            'inscriptions.formation',

            'diplomes',

            'boiteIdees'

        ])->findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | UN APPRENANT PEUT UNIQUEMENT VOIR SON PROPRE DOSSIER
        |--------------------------------------------------------------------------
        */

        if (
            $user->role === 'apprenant'
            &&
            $apprenant->user_id != $user->id
        ) {

            return response()->json([
                'message' =>
                    'Accès refusé'
            ], 403);
        }


        return response()->json(
            $apprenant
        );
    }


    /**
     * =========================================================
     * MODIFICATION APPRENANT
     * =========================================================
     */
    public function update(
        Request $request,
        string $id
    ) {

        $user = Auth::guard('api')->user();


        /*
        |--------------------------------------------------------------------------
        | VÉRIFICATION ADMIN / GESTIONNAIRE
        |--------------------------------------------------------------------------
        */

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


        /*
        |--------------------------------------------------------------------------
        | RÉCUPÉRER L'APPRENANT AVEC SON USER
        |--------------------------------------------------------------------------
        */

        $apprenant = Apprenant::with('user')
            ->findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([

            /*
            |--------------------------------------------------------------------------
            | INFORMATIONS APPRENANT
            |--------------------------------------------------------------------------
            */

            'numero_cni' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique(
                    'apprenants',
                    'numero_cni'
                )->ignore(
                    $apprenant->id
                )
            ],

            'date_naissance' => [
                'required',
                'date'
            ],

            'sexe' => [
                'required',
                Rule::in([
                    'Masculin',
                    'Feminin'
                ])
            ],

            'situation_matrimoniale' => [
                'required',
                Rule::in([
                    'Celibataire',
                    'Marie',
                    'Divorce',
                    'Veuf'
                ])
            ],

            'niveau_informatique' => [
                'required',
                Rule::in([
                    'Debutant',
                    'Intermediaire',
                    'Avance'
                ])
            ],

            'adresse' => [
                'required',
                'string'
            ],

            'telephone' => [
                'required',
                'string'
            ],

            'niveau_etude' => [
                'required',
                'string'
            ],

            'fonction' => [
                'nullable',
                'string'
            ],

            'statut' => [
                'nullable',
                Rule::in([
                    'En attente',
                    'Valide',
                    'Refuse'
                ])
            ],

            'photo' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048'
            ],

            /*
            |--------------------------------------------------------------------------
            | SIGNATURE
            |--------------------------------------------------------------------------
            |
            | La signature provenant du canvas est une chaîne
            | Base64.
            |
            */

            'signature' => [
                'nullable',
                'string'
            ],


            /*
            |--------------------------------------------------------------------------
            | INFORMATIONS USER
            |--------------------------------------------------------------------------
            */

            'nom' => [
                'required',
                'string',
                'max:255'
            ],

            'prenom' => [
                'required',
                'string',
                'max:255'
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(
                    'users',
                    'email'
                )->ignore(
                    $apprenant->user_id
                )
            ],

        ]);


        /*
        |--------------------------------------------------------------------------
        | TRANSACTION
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | MODIFICATION DU COMPTE USER
            |--------------------------------------------------------------------------
            */

            if ($apprenant->user) {

                $apprenant->user->update([

                    'nom' =>
                        $validated['nom'],

                    'prenom' =>
                        $validated['prenom'],

                    'email' =>
                        $validated['email'],

                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | DONNÉES DE L'APPRENANT
            |--------------------------------------------------------------------------
            */

            $donneesApprenant = [

                'numero_cni' =>
                    $validated['numero_cni']
                    ?? null,

                'date_naissance' =>
                    $validated['date_naissance'],

                'sexe' =>
                    $validated['sexe'],

                'situation_matrimoniale' =>
                    $validated['situation_matrimoniale'],

                'niveau_informatique' =>
                    $validated['niveau_informatique'],

                'adresse' =>
                    $validated['adresse'],

                'telephone' =>
                    $validated['telephone'],

                'niveau_etude' =>
                    $validated['niveau_etude'],

                'fonction' =>
                    $validated['fonction']
                    ?? null,

                'statut' =>
                    $validated['statut']
                    ?? $apprenant->statut,

            ];


            /*
            |--------------------------------------------------------------------------
            | GESTION DE LA SIGNATURE
            |--------------------------------------------------------------------------
            |
            | has('signature') est utilisé au lieu de filled().
            |
            | Cela permet :
            |
            | - nouvelle signature → remplacement
            | - signature vide → suppression
            | - signature non envoyée → conservation
            |
            */

            if ($request->has('signature')) {

                $donneesApprenant['signature'] =
                    $request->input('signature');
            }


            /*
            |--------------------------------------------------------------------------
            | GESTION DE LA PHOTO
            |--------------------------------------------------------------------------
            */

            if ($request->hasFile('photo')) {

                /*
                | Supprimer l'ancienne photo
                */

                if ($apprenant->photo) {

                    Storage::disk('public')
                        ->delete(
                            $apprenant->photo
                        );
                }


                /*
                | Enregistrer la nouvelle photo
                */

                $donneesApprenant['photo'] =
                    $request
                        ->file('photo')
                        ->store(
                            'apprenants',
                            'public'
                        );
            }


            /*
            |--------------------------------------------------------------------------
            | MODIFIER L'APPRENANT
            |--------------------------------------------------------------------------
            */

            $apprenant->update(
                $donneesApprenant
            );


            /*
            |--------------------------------------------------------------------------
            | VALIDER LA TRANSACTION
            |--------------------------------------------------------------------------
            */

            DB::commit();


            /*
            |--------------------------------------------------------------------------
            | RÉPONSE
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'message' =>
                    'Apprenant modifié avec succès.',

                'data' =>
                    $apprenant
                        ->fresh()
                        ->load([
                            'user',
                            'inscriptions.formation',
                            'diplomes',
                            'boiteIdees'
                        ])

            ], 200);


        } catch (\Throwable $e) {

            /*
            |--------------------------------------------------------------------------
            | ANNULER EN CAS D'ERREUR
            |--------------------------------------------------------------------------
            */

            DB::rollBack();


            return response()->json([

                'message' =>
                    'Erreur lors de la modification de l’apprenant.',

                'error' =>
                    $e->getMessage()

            ], 500);
        }
    }


    /**
     * =========================================================
     * SUPPRESSION APPRENANT
     * =========================================================
     */
    public function destroy(string $id)
    {
        $user = Auth::guard('api')->user();

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


        $apprenant = Apprenant::findOrFail($id);


        /*
        |--------------------------------------------------------------------------
        | SUPPRESSION DE LA PHOTO
        |--------------------------------------------------------------------------
        */

        if ($apprenant->photo) {

            Storage::disk('public')
                ->delete(
                    $apprenant->photo
                );
        }


        /*
        |--------------------------------------------------------------------------
        | SUPPRESSION DU DOSSIER APPRENANT
        |--------------------------------------------------------------------------
        */

        $apprenant->delete();


        return response()->json([
            'message' =>
                'Apprenant supprimé avec succès.'
        ]);
    }
}
