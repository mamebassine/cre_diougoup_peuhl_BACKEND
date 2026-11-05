<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiplomeAttestation;
use App\Models\Apprenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DiplomeAttestationController extends Controller
{
    /**
     * LISTE DE TOUTES LES DEMANDES
     * Admin / Gestionnaire
     */
    public function index()
    {
        $demandes = DiplomeAttestation::with('apprenant')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $demandes
        ]);
    }

    /**
     * L'APPRENANT ENVOIE UNE DEMANDE
     */
    public function demande(Request $request)
    {
        $validated = $request->validate([

            // Type de document
            'type_document' => 'required|in:Diplome,Attestation',

            // Informations personnelles
            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'adresse' => 'required|string|max:255',
            'fonction' => 'nullable|string|max:255',

            // Informations sur la formation
            'formation' => 'required|string|max:255',
            'duree_formation' => 'nullable|string|max:255',
            'periode_formation' => 'nullable|string|max:255',
            'module_suivi' => 'nullable|string|max:255',
            'annee_academique' => 'nullable|string|max:255',

            // Lieu de rédaction
            'lieu_demande' => 'nullable|string|max:255',
        ]);

        // Récupération de l'utilisateur connecté
        $user = auth()->guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.'
            ], 401);
        }

        // Recherche du profil apprenant
        $apprenant = Apprenant::where('user_id', $user->id)->first();

        if (!$apprenant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil apprenant introuvable.'
            ], 404);
        }

        // Création de la demande
        $demande = DiplomeAttestation::create(array_merge(
            $validated,
            [
                'apprenant_id' => $apprenant->id,
                'date_demande' => now()->toDateString(),
                'statut' => 'en_attente'
            ]
        ));

        return response()->json([
            'success' => true,
            'message' => 'Votre demande a été envoyée avec succès.',
            'data' => $demande
        ], 201);
    }

    /**
     * L'APPRENANT CONSULTE SES DEMANDES
     */
    public function mesDemandes()
    {
        $user = auth()->guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur non authentifié.'
            ], 401);
        }

        $apprenant = Apprenant::where('user_id', $user->id)->first();

        if (!$apprenant) {
            return response()->json([
                'success' => false,
                'message' => 'Profil apprenant introuvable.'
            ], 404);
        }

        $demandes = DiplomeAttestation::where(
            'apprenant_id',
            $apprenant->id
        )->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $demandes
        ]);
    }

    /**
     * CRÉATION DIRECTE D'UN DOCUMENT
     * Admin / Gestionnaire
     */
    public function store(Request $request)
    {
        $validated = $request->validate([

            'apprenant_id' => 'required|exists:apprenants,id',
            'type_document' => 'required|in:Diplome,Attestation',

            'prenom' => 'required|string|max:255',
            'nom' => 'required|string|max:255',
            'date_naissance' => 'required|date|before:today',
            'lieu_naissance' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'adresse' => 'required|string|max:255',

            'fonction' => 'nullable|string|max:255',
            'formation' => 'required|string|max:255',
            'duree_formation' => 'nullable|string|max:255',
            'periode_formation' => 'nullable|string|max:255',
            'module_suivi' => 'nullable|string|max:255',
            'annee_academique' => 'nullable|string|max:255',

            'numero_document' => [
                'required',
                'string',
                'max:255',
                'unique:diplome_attestations,numero_document'
            ],

            'date_delivrance' => 'required|date',

            'fichier_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Enregistrement du PDF officiel
        if ($request->hasFile('fichier_pdf')) {
            $validated['fichier_pdf'] = $request
                ->file('fichier_pdf')
                ->store('diplomes', 'public');
        }

        $validated['date_demande'] = now()->toDateString();
        $validated['date_validation'] = now()->toDateString();
        $validated['statut'] = 'valide';

        $document = DiplomeAttestation::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Document créé avec succès.',
            'data' => $document
        ], 201);
    }

    /**
     * VALIDATION OU REFUS D'UNE DEMANDE
     * Admin / Gestionnaire
     */
    public function update(Request $request, string $id)
    {
        $document = DiplomeAttestation::findOrFail($id);

        $validated = $request->validate([

            'statut' => 'required|in:valide,refuse,en_attente',

            'numero_document' => [
                'nullable',
                'required_if:statut,valide',
                'string',
                'max:255',
                Rule::unique('diplome_attestations', 'numero_document')
                    ->ignore($document->id)
            ],

            'date_delivrance' => 'nullable|required_if:statut,valide|date',

            'motif_refus' => 'nullable|required_if:statut,refuse|string',

            'fichier_pdf' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Ajout ou remplacement du PDF officiel
        if ($request->hasFile('fichier_pdf')) {

            if ($document->fichier_pdf) {
                Storage::disk('public')->delete($document->fichier_pdf);
            }

            $validated['fichier_pdf'] = $request
                ->file('fichier_pdf')
                ->store('diplomes', 'public');
        }

        // Date de traitement administratif
        if (in_array($validated['statut'], ['valide', 'refuse'])) {
            $validated['date_validation'] = now()->toDateString();
        }

        // Enregistrement des modifications
        $document->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Demande mise à jour avec succès.',
            'data' => $document->fresh()
        ]);
    }

    /**
     * AFFICHER UNE DEMANDE
     * Admin / Gestionnaire
     */
    public function show(string $id)
    {
        $document = DiplomeAttestation::with('apprenant')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $document
        ]);
    }

    /**
     * ENREGISTRER LE RETRAIT PHYSIQUE
     * Admin / Gestionnaire
     */
    public function marquerRetrait(string $id)
    {
        $document = DiplomeAttestation::findOrFail($id);

        if ($document->statut !== 'valide') {
            return response()->json([
                'success' => false,
                'message' => 'Seuls les documents validés peuvent être retirés.'
            ], 422);
        }

        if (!$document->fichier_pdf) {
            return response()->json([
                'success' => false,
                'message' => 'Le document officiel doit être ajouté avant le retrait.'
            ], 422);
        }

        $document->update([
            'statut' => 'retire',
            'date_retrait' => now()->toDateString()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Retrait du document enregistré avec succès.',
            'data' => $document
        ]);
    }

    /**
     * SUPPRIMER UNE DEMANDE
     * Admin / Gestionnaire
     */
    public function destroy(string $id)
    {
        $document = DiplomeAttestation::findOrFail($id);

        // Suppression des fichiers associés
        if ($document->fichier_pdf) {
            Storage::disk('public')->delete($document->fichier_pdf);
        }

        if ($document->fichier_demande) {
            Storage::disk('public')->delete($document->fichier_demande);
        }

        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Demande supprimée avec succès.'
        ]);
    }
}
