<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BoiteIdee;
use Illuminate\Http\Request;

class BoiteIdeeController extends Controller
{
    public function index()
    {
        return response()->json(
            BoiteIdee::with('apprenant')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'apprenant_id' => 'required|exists:apprenants,id',
            'type_message' => 'required',
            'objet' => 'required',
            'message' => 'required'
        ]);

        $idee = BoiteIdee::create($request->all());

        return response()->json([
            'message' => 'Message envoyé',
            'data' => $idee
        ],201);
    }

    public function show(string $id)
    {
        return response()->json(
            BoiteIdee::with('apprenant')
            ->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $idee = BoiteIdee::findOrFail($id);

        $idee->update($request->all());

        return response()->json([
            'message' => 'Message modifié',
            'data' => $idee
        ]);
    }

    public function destroy(string $id)
    {
        BoiteIdee::destroy($id);

        return response()->json([
            'message' => 'Message supprimé'
        ]);
    }
}