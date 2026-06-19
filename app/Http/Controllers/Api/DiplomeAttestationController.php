<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DiplomeAttestation;
use Illuminate\Http\Request;

class DiplomeAttestationController extends Controller
{
    public function index()
    {
        return response()->json(
            DiplomeAttestation::with('apprenant')->latest()->get()
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

        $document = DiplomeAttestation::create($request->all());

        return response()->json([
            'message' => 'Document créé',
            'data' => $document
        ],201);
    }

    public function show(string $id)
    {
        return response()->json(
            DiplomeAttestation::with('apprenant')
            ->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $document = DiplomeAttestation::findOrFail($id);

        $document->update($request->all());

        return response()->json([
            'message' => 'Document modifié',
            'data' => $document
        ]);
    }

    public function destroy(string $id)
    {
        DiplomeAttestation::destroy($id);

        return response()->json([
            'message' => 'Document supprimé'
        ]);
    }
}