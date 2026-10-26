<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use App\Models\Apprenant;
use App\Models\Formation;
use App\Models\User;
use App\Notifications\GeneralNotification;
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

        // ADMIN / GESTIONNAIRE
        if (in_array($user->role, ['admin', 'gestionnaire'])) {
            return response()->json(
                Inscription::with([
                    'apprenant.user',
                    'formation',
                    'createur'
                ])->latest()->get()
            );
        }

        // APPRENANT
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
        // L'apprenant doit avoir au moins 11 ans.
        $dateLimite = now()->subYears(11)->toDateString();

        $request->validate([
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'required|string|max:30',
            'date_naissance' => 'required|date|before_or_equal:' . $dateLimite,
            'sexe' => 'required|in:Masculin,Feminin',
            'situation_matrimoniale' => 'required|in:Celibataire,Marie,Divorce,Veuf',
            'niveau_etude' => 'required|string|max:255',
            'niveau_informatique' => 'required|in:Debutant,Intermediaire,Avance',
            'adresse' => 'required|string|max:255',

            // CNI : 13 chiffres, unique, préfixe selon le sexe.
            'numero_cni' => [
                'required',
                'digits:13',
                'unique:apprenants,numero_cni',
                function ($attribute, $value, $fail) use ($request) {
                    $premierChiffre = substr($value, 0, 1);

                    if (
                        $request->sexe === 'Masculin' &&
                        $premierChiffre !== '1'
                    ) {
                        $fail(
                            'Pour un homme, le numéro CNI doit commencer par 1.'
                        );
                    }

                    if (
                        $request->sexe === 'Feminin' &&
                        $premierChiffre !== '2'
                    ) {
                        $fail(
                            'Pour une femme, le numéro CNI doit commencer par 2.'
                        );
                    }
                },
            ],

            'signature' => 'required|string',
            'formation_id' => 'required|exists:formations,id',
            'horaire' => 'required|string|max:100',
        ]);

        $formation = Formation::findOrFail(
            $request->formation_id
        );

        if (!$formation->is_active) {
            return response()->json([
                'message' => 'Cette formation n’est plus disponible.'
            ], 409);
        }

        // Vérifier la capacité de la formation.
        if ($formation->capacite !== null) {
            $nombreInscrits = Inscription::where(
                'formation_id',
                $formation->id
            )->where('statut', 'Valide')->count();

            if ($nombreInscrits >= $formation->capacite) {
                return response()->json([
                    'message' =>
                        'La capacité maximale de cette formation est atteinte.'
                ], 409);
            }
        }

        // Créer le compte utilisateur.
        $nouveauUser = User::create([
            'nom' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'telephone' => $request->telephone,
            'password' => Hash::make($request->password),
            'role' => 'apprenant',
            'is_active' => true,
        ]);

        // Créer le dossier apprenant.
        $apprenant = Apprenant::create([
            'created_by' => $nouveauUser->id,
            'user_id' => $nouveauUser->id,
            'matricule' => $this->genererMatricule(),
            'numero_cni' => $request->numero_cni,
            'date_naissance' => $request->date_naissance,
            'sexe' => $request->sexe,
            'situation_matrimoniale' => $request->situation_matrimoniale,
            'niveau_informatique' => $request->niveau_informatique,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'niveau_etude' => $request->niveau_etude,
            'fonction' => $request->fonction,
            'signature' => $request->signature,
        ]);

        // Vérifier si l'apprenant est déjà inscrit.
        $existe = Inscription::where(
            'apprenant_id',
            $apprenant->id
        )->where('formation_id', $formation->id)->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Vous êtes déjà inscrit à cette formation.'
            ], 409);
        }

        // Créer la demande d'inscription.
        $inscription = Inscription::create([
            'apprenant_id' => $apprenant->id,
            'formation_id' => $formation->id,
            'horaire' => $request->horaire,
            'date_inscription' => now(),
            'statut' => 'En attente',
            'etat_formation' => 'Non commencée',
            'created_by' => null,
        ]);

        // NOTIFICATION : prévenir les administrateurs et gestionnaires.
        $this->notifierAdministration($inscription);

        return response()->json([
            'success' => true,
            'message' =>
                'Votre demande d’inscription a été envoyée avec succès.',
            'data' => [
                'inscription' => $inscription->load('formation'),
                'apprenant' => $apprenant->load('user'),
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
        $dernier = Apprenant::latest('id')->first();

        if ($dernier && $dernier->matricule) {
            $numero = intval(substr($dernier->matricule, 6)) + 1;
        } else {
            $numero = 1;
        }

        return 'CRE-DP' . str_pad(
            $numero,
            4,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * =========================================================
     * NOTIFIER LES ADMINISTRATEURS ET GESTIONNAIRES
     * =========================================================
     */
    private function notifierAdministration(
        Inscription $inscription
    ): void {
        $inscription->loadMissing([
            'apprenant.user',
            'formation'
        ]);

        $apprenant = $inscription->apprenant;
        $formation = $inscription->formation;

        $nomApprenant = trim(
            ($apprenant->user->prenom ?? '') . ' ' .
            ($apprenant->user->nom ?? '')
        );

        $nomFormation = $formation->titre
            ?? $formation->nom
            ?? 'Formation';

        $destinataires = User::whereIn(
            'role',
            ['admin', 'gestionnaire']
        )->where('is_active', true)->get();

        foreach ($destinataires as $destinataire) {
            try {
                $destinataire->notify(
                    new GeneralNotification(
                        'Nouvelle demande d’inscription',
                        $nomApprenant .
                            ' demande une inscription à la formation ' .
                            $nomFormation . '.',
                        'inscription'
                    )
                );
            } catch (\Throwable $e) {
                // Une erreur de notification ne bloque pas l'inscription.
                report($e);
            }
        }
    }

    /**
     * =========================================================
     * INSCRIPTION APPRENANT CONNECTÉ
     * =========================================================
     */
    public function store(Request $request)
    {
        $request->validate([
            'formation_id' => 'required|exists:formations,id',
            'horaire' => 'required|string',
        ]);

        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non connecté.'
            ], 401);
        }

        $apprenant = Apprenant::where(
            'user_id',
            $user->id
        )->first();

        if (!$apprenant) {
            return response()->json([
                'message' =>
                    'Vous devez d’abord créer votre dossier apprenant.'
            ], 404);
        }

        $formation = Formation::findOrFail(
            $request->formation_id
        );

        if (!$formation->is_active) {
            return response()->json([
                'message' => 'Cette formation n’est plus disponible.'
            ], 409);
        }

        $existe = Inscription::where(
            'apprenant_id',
            $apprenant->id
        )->where('formation_id', $formation->id)->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Vous êtes déjà inscrit à cette formation.'
            ], 409);
        }

        if ($formation->capacite !== null) {
            $nombreInscrits = Inscription::where(
                'formation_id',
                $formation->id
            )->where('statut', 'Valide')->count();

            if ($nombreInscrits >= $formation->capacite) {
                return response()->json([
                    'message' =>
                        'La capacité maximale de cette formation est atteinte.'
                ], 409);
            }
        }

        $inscription = Inscription::create([
            'apprenant_id' => $apprenant->id,
            'formation_id' => $formation->id,
            'horaire' => $request->horaire,
            'date_inscription' => now(),
            'statut' => 'En attente',
            'etat_formation' => 'Non commencée',
            'created_by' => null,
        ]);

        // NOTIFICATION : nouvelle demande pour l'administration.
        $this->notifierAdministration($inscription);

        return response()->json([
            'message' => 'Demande envoyée avec succès.',
            'data' => $inscription->load('formation'),
        ], 201);
    }

    /**
     * =========================================================
     * INSCRIPTION ADMIN
     * =========================================================
     */
    public function inscriptionAdmin(Request $request)
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

        $request->validate([
            'apprenant_id' => 'required|exists:apprenants,id',
            'formation_id' => 'required|exists:formations,id',
            'horaire' => 'required|string',
        ]);

        $formation = Formation::findOrFail(
            $request->formation_id
        );

        if (!$formation->is_active) {
            return response()->json([
                'message' => 'Cette formation n’est plus disponible.'
            ], 409);
        }

        $existe = Inscription::where(
            'apprenant_id',
            $request->apprenant_id
        )->where('formation_id', $request->formation_id)->exists();

        if ($existe) {
            return response()->json([
                'message' => 'Cet apprenant est déjà inscrit.'
            ], 409);
        }

        if ($formation->capacite !== null) {
            $nombreInscrits = Inscription::where(
                'formation_id',
                $formation->id
            )->where('statut', 'Valide')->count();

            if ($nombreInscrits >= $formation->capacite) {
                return response()->json([
                    'message' =>
                        'La capacité maximale de cette formation est atteinte.'
                ], 409);
            }
        }

        $inscription = Inscription::create([
            'apprenant_id' => $request->apprenant_id,
            'formation_id' => $request->formation_id,
            'horaire' => $request->horaire,
            'date_inscription' => now(),
            'statut' => 'Valide',
            'etat_formation' => 'Non commencée',
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Apprenant inscrit par administration.',
            'data' => $inscription->load([
                'apprenant.user',
                'formation',
                'createur'
            ])
        ], 201);
    }

    /**
     * =========================================================
     * CRÉER UN COMPTE + APPRENANT + INSCRIPTION
     * ADMIN / GESTIONNAIRE
     * =========================================================
     */
    public function inscriptionCompleteAdmin(Request $request)
    {
        $user = Auth::guard('api')->user();

        if (
            !$user ||
            !in_array($user->role, ['admin', 'gestionnaire'])
        ) {
            return response()->json([
                'message' => 'Accès refusé.'
            ], 403);
        }

        $request->validate([
            // Compte
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'telephone' => 'required|string|max:30',

            // Dossier apprenant
            'numero_cni' => [
                'required',
                'digits:13',
                'unique:apprenants,numero_cni',
                function ($attribute, $value, $fail) use ($request) {
                    $premierChiffre = substr($value, 0, 1);

                    if (
                        $request->sexe === 'Masculin' &&
                        $premierChiffre !== '1'
                    ) {
                        $fail(
                            'Pour un homme, le numéro CNI doit commencer par 1.'
                        );
                    }

                    if (
                        $request->sexe === 'Feminin' &&
                        $premierChiffre !== '2'
                    ) {
                        $fail(
                            'Pour une femme, le numéro CNI doit commencer par 2.'
                        );
                    }
                },
            ],

            'date_naissance' => 'required|date',
            'sexe' => 'required|in:Masculin,Feminin',
            'situation_matrimoniale' =>
                'required|in:Celibataire,Marie,Divorce,Veuf',
            'niveau_etude' => 'required|string|max:255',
            'niveau_informatique' =>
                'required|in:Debutant,Intermediaire,Avance',
            'adresse' => 'required|string|max:255',
            'fonction' => 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',

            // Inscription
            'formation_id' => 'required|exists:formations,id',
            'horaire' => 'required|string|max:100',
        ]);

        $formation = Formation::findOrFail(
            $request->formation_id
        );

        if (!$formation->is_active) {
            return response()->json([
                'message' => 'Cette formation n’est plus disponible.'
            ], 409);
        }

        if ($formation->capacite !== null) {
            $nombreInscrits = Inscription::where(
                'formation_id',
                $formation->id
            )->where('statut', 'Valide')->count();

            if ($nombreInscrits >= $formation->capacite) {
                return response()->json([
                    'message' =>
                        'La capacité maximale de cette formation est atteinte.'
                ], 409);
            }
        }

        DB::beginTransaction();

        try {
            // Créer le compte utilisateur.
            $nouveauUser = User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'email' => $request->email,
                'telephone' => $request->telephone,
                'password' => Hash::make($request->password),
                'role' => 'apprenant',
                'is_active' => true,
            ]);

            // Enregistrer la photo si elle a été fournie.
            $photoPath = null;

            if ($request->hasFile('photo')) {
                $photoPath = $request->file('photo')->store(
                    'apprenants',
                    'public'
                );
            }

            $matricule = $this->genererMatricule();

            // Créer le dossier apprenant.
            $apprenant = Apprenant::create([
                'created_by' => $user->id,
                'user_id' => $nouveauUser->id,
                'matricule' => $matricule,
                'numero_cni' => $request->numero_cni,
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

            // L'inscription créée par l'administration est validée.
            $inscription = Inscription::create([
                'apprenant_id' => $apprenant->id,
                'formation_id' => $formation->id,
                'horaire' => $request->horaire,
                'date_inscription' => now(),
                'statut' => 'Valide',
                'etat_formation' => 'Non commencée',
                'created_by' => $user->id,
            ]);

            DB::commit();

            // NOTIFICATION : informer le nouvel apprenant.
            try {
                $nomFormation = $formation->titre
                    ?? $formation->nom
                    ?? 'sélectionnée';

                $nouveauUser->notify(
                    new GeneralNotification(
                        'Inscription effectuée',
                        'Votre compte a été créé et vous avez été inscrit à la formation ' .
                            $nomFormation . '.',
                        'inscription',
                        'Valide'
                    )
                );
            } catch (\Throwable $e) {
                // Ne pas annuler une inscription déjà enregistrée.
                report($e);
            }

            return response()->json([
                'success' => true,
                'message' =>
                    'Compte apprenant créé et inscrit avec succès.',
                'data' => [
                    'user' => $nouveauUser,
                    'apprenant' => $apprenant->load('user'),
                    'inscription' => $inscription->load([
                        'apprenant.user',
                        'formation',
                        'createur'
                    ]),
                ],
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' =>
                    'Une erreur est survenue lors de la création de l’apprenant.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * =========================================================
     * DÉTAIL D'UNE INSCRIPTION
     * =========================================================
     */
    public function show(string $id)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non connecté.'
            ], 401);
        }

        $inscription = Inscription::with([
            'apprenant.user',
            'formation',
            'createur'
        ])->findOrFail($id);

        if (
            $user->role === 'apprenant' &&
            $inscription->apprenant->user_id != $user->id
        ) {
            return response()->json([
                'message' => 'Accès refusé.'
            ], 403);
        }

        return response()->json($inscription);
    }

    /**
     * =========================================================
     * MODIFIER UNE INSCRIPTION
     * =========================================================
     */
    public function update(Request $request, string $id)
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Utilisateur non connecté.'
            ], 401);
        }

        $inscription = Inscription::findOrFail($id);

        // ADMIN / GESTIONNAIRE
        if (in_array($user->role, ['admin', 'gestionnaire'])) {
            $request->validate([
                'statut' =>
                    'sometimes|in:En attente,Valide,Refuse',
                'etat_formation' =>
                    'sometimes|in:Non commencée,En cours,Terminée,Abandonnée',
                'formation_id' => 'sometimes|exists:formations,id',
                'horaire' => 'sometimes|string|max:100',
            ]);

            $data = [];

            if ($request->has('statut')) {
                $data['statut'] = $request->statut;
            }

            if ($request->has('etat_formation')) {
                $data['etat_formation'] = $request->etat_formation;
            }

            if ($request->has('formation_id')) {
                $formation = Formation::findOrFail(
                    $request->formation_id
                );

                if (!$formation->is_active) {
                    return response()->json([
                        'message' =>
                            'Cette formation n’est plus disponible.'
                    ], 409);
                }

                $data['formation_id'] = $formation->id;
            }

            if ($request->has('horaire')) {
                $data['horaire'] = $request->horaire;
            }

            $data['created_by'] = $user->id;

            // Garder l'ancien statut pour vérifier s'il a changé.
            $ancienStatut = $inscription->statut;

            $inscription->update($data);

            // NOTIFICATION : prévenir l'apprenant si le statut change.
            if (
                isset($data['statut']) &&
                $ancienStatut !== $inscription->statut
            ) {
                $inscription->loadMissing('apprenant.user');

                $apprenantUser = $inscription->apprenant?->user;

                if ($apprenantUser) {
                    try {
                        $apprenantUser->notify(
                            new GeneralNotification(
                                'Mise à jour de votre inscription',
                                'Le statut de votre inscription est maintenant : ' .
                                    $inscription->statut . '.',
                                'inscription',
                                $inscription->statut
                            )
                        );
                    } catch (\Throwable $e) {
                        // La mise à jour reste enregistrée même si la
                        // notification rencontre une erreur.
                        report($e);
                    }
                }
            }

            return response()->json([
                'message' => 'Inscription mise à jour.',
                'data' => $inscription->load([
                    'apprenant.user',
                    'formation',
                    'createur'
                ])
            ]);
        }

        // APPRENANT
        if ($user->role === 'apprenant') {
            $apprenant = Apprenant::where(
                'user_id',
                $user->id
            )->first();

            if (!$apprenant) {
                return response()->json([
                    'message' => 'Dossier apprenant introuvable.'
                ], 404);
            }

            if ($inscription->apprenant_id !== $apprenant->id) {
                return response()->json([
                    'message' =>
                        'Vous ne pouvez pas modifier l’inscription d’un autre apprenant.'
                ], 403);
            }

            $request->validate([
                'formation_id' => 'required|exists:formations,id',
                'horaire' => 'required|string|max:100',
            ]);

            $formation = Formation::findOrFail(
                $request->formation_id
            );

            if (!$formation->is_active) {
                return response()->json([
                    'message' =>
                        'Cette formation n’est plus disponible.'
                ], 409);
            }

            $dejaInscrit = Inscription::where(
                'apprenant_id',
                $apprenant->id
            )->where(
                'formation_id',
                $formation->id
            )->where(
                'id',
                '!=',
                $inscription->id
            )->exists();

            if ($dejaInscrit) {
                return response()->json([
                    'message' =>
                        'Vous êtes déjà inscrit à cette formation.'
                ], 409);
            }

            $inscription->update([
                'formation_id' => $formation->id,
                'horaire' => $request->horaire,
            ]);

            return response()->json([
                'success' => true,
                'message' =>
                    'Votre inscription a été modifiée avec succès.',
                'data' => $inscription->load('formation')
            ]);
        }

        return response()->json([
            'message' => 'Accès refusé.'
        ], 403);
    }

    /**
     * =========================================================
     * SUPPRIMER UNE INSCRIPTION
     * =========================================================
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

        $inscription = Inscription::findOrFail($id);
        $inscription->delete();

        return response()->json([
            'message' => 'Inscription supprimée.'
        ]);
    }
}