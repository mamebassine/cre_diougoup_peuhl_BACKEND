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
     * Admin / gestionnaire voient tout
     */
    public function index()
    {
        return response()->json(
            DiplomeAttestation::with('apprenant')->latest()->get()
        );
    }


    /**
 * L'apprenant envoie une demande de diplôme
 */
public function demande(Request $request)
{
    $request->validate([
        'type_document' => 'required',
        'numero_document' => 'required',
        'date_delivrance' => 'required|date'
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
        'numero_document' => $request->numero_document,
        'date_delivrance' => $request->date_delivrance,
        'statut' => 'en_attente'
    ]);

    return response()->json([
        'message' => 'Demande envoyée avec succès.',
        'data' => $demande
    ], 201);
}


    /**
     * DEMANDE DIPLOME (APPRENANT)
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
    public function store(Request $request)
{
    $request->validate([
        'apprenant_id' => 'required|exists:apprenants,id',
        'type_document' => 'required',
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
     * VALIDATION (ADMIN / GESTIONNAIRE)
     */
    public function update(Request $request, string $id)
    {
        $document = DiplomeAttestation::findOrFail($id);

        $request->validate([
            'statut' => 'required|in:valide,refuse,en_attente'
        ]);

        $document->update([
            'statut' => $request->statut
        ]);

        return response()->json([
            'message' => 'Statut mis à jour',
            'data' => $document
        ]);
    }

    public function show(string $id)
    {
        return response()->json(
            DiplomeAttestation::with('apprenant')->findOrFail($id)
        );
    }

    public function destroy(string $id)
    {
        DiplomeAttestation::destroy($id);

        return response()->json([
            'message' => 'Document supprimé'
        ]);
    }
}