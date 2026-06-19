<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apprenant;
use Illuminate\Http\Request;

class ApprenantController extends Controller
{
    public function index()
    {
        return response()->json(
            Apprenant::with('user')->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id|unique:apprenants,user_id',
            'matricule' => 'required|unique:apprenants,matricule',
            'date_naissance' => 'required|date',
            'sexe' => 'required',
            'situation_matrimoniale' => 'required',
            'adresse' => 'required',
            'telephone' => 'required',
            'niveau_etude' => 'required',
            'niveau_informatique' => 'required',
            'module_choisi' => 'required',
            'horaire_choisi' => 'required'
        ]);

        $apprenant = Apprenant::create($request->all());

        return response()->json([
            'message' => 'Apprenant créé avec succès',
            'data' => $apprenant
        ],201);
    }

    public function show(string $id)
    {
        return response()->json(
            Apprenant::with([
                'user',
                'diplomes',
                'boiteIdees'
            ])->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $apprenant = Apprenant::findOrFail($id);

        $apprenant->update($request->all());

        return response()->json([
            'message' => 'Apprenant modifié',
            'data' => $apprenant
        ]);
    }

    public function destroy(string $id)
    {
        $apprenant = Apprenant::findOrFail($id);

        $apprenant->delete();

        return response()->json([
            'message' => 'Apprenant supprimé'
        ]);
    }
}