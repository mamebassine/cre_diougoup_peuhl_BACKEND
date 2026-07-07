<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiplomeAttestation;
use App\Models\Apprenant;
use Illuminate\Http\Request;

class DiplomeAttestationController extends Controller
{
    /**
     * LISTE
     * Admin / Gestionnaire
     */
    public function index()
    {
        return response()->json(
            DiplomeAttestation::with('apprenant')->latest()->get()
        );
    }

    /**
     * L'apprenant envoie une demande de diplôme/attestation
     */
    public function demande(Request $request)
    {
        $request->validate([
            'type_document' => 'required|string'
        ]);

        $user = auth()->guard('api')->user();

        $apprenant = Apprenant::where('user_id', $user->id)->first();

        if (!$apprenant) {
            return response()->json([
                'message' => 'Apprenant introuvable.'
            ], 404);
        }

        $demande = DiplomeAttestation::create([
            'apprenant_id' => $apprenant->id,
            'type_document' => $request->type_document,
            'statut' => 'en_attente'
        ]);

        return response()->json([
            'message' => 'Demande envoyée avec succès.',
            'data' => $demande
        ], 201);
    }

    /**
     * L'apprenant consulte ses demandes
     */
    public function mesDemandes()
    {
        $user = auth()->guard('api')->user();

        $apprenant = Apprenant::where('user_id', $user->id)->first();

        if (!$apprenant) {
            return response()->json([
                'message' => 'Apprenant introuvable.'
            ], 404);
        }

        return response()->json(
            DiplomeAttestation::where('apprenant_id', $apprenant->id)
                ->latest()
                ->get()
        );
    }

    /**
     * Création directe d'un document (Admin / Gestionnaire)
     */
    public function store(Request $request)
    {
        $request->validate([
            'apprenant_id' => 'required|exists:apprenants,id',
            'type_document' => 'required|string',
            'numero_document' => 'required|unique:diplome_attestations,numero_document',
            'date_delivrance' => 'required|date'
        ]);

        $document = DiplomeAttestation::create([
            'apprenant_id' => $request->apprenant_id,
            'type_document' => $request->type_document,
            'numero_document' => $request->numero_document,
            'date_delivrance' => $request->date_delivrance,
            'statut' => 'valide'
        ]);

        return response()->json([
            'message' => 'Document créé avec succès.',
            'data' => $document
        ], 201);
    }

    /**
     * Validation d'une demande (Admin / Gestionnaire)
     */
    public function update(Request $request, string $id)
    {
        $document = DiplomeAttestation::findOrFail($id);

        $request->validate([
            'numero_document' => 'required|unique:diplome_attestations,numero_document,' . $document->id,
            'date_delivrance' => 'required|date',
            'statut' => 'required|in:valide,refuse,en_attente'
        ]);

        $document->update([
            'numero_document' => $request->numero_document,
            'date_delivrance' => $request->date_delivrance,
            'statut' => $request->statut
        ]);

        return response()->json([
            'message' => 'Document mis à jour avec succès.',
            'data' => $document
        ]);
    }

    /**
     * Afficher un document
     */
    public function show(string $id)
    {
        return response()->json(
            DiplomeAttestation::with('apprenant')->findOrFail($id)
        );
    }

    /**
     * Supprimer un document
     */
    public function destroy(string $id)
    {
        $document = DiplomeAttestation::findOrFail($id);

        $document->delete();

        return response()->json([
            'message' => 'Document supprimé avec succès.'
        ]);
    }
}