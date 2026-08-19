<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Formation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FormationController extends Controller
{
    /**
     * LISTE DES FORMATIONS ACTIVES
     */
    public function index()
    {
        $formations = Formation::where('is_active', true)
            ->latest()
            ->paginate(6);

        return response()->json($formations);
    }

    /**
     * CREER UNE FORMATION
     */
    public function store(Request $request)
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
            'nom' => 'required|string|unique:formations,nom',
            'resume' => 'required|string',
            'description' => 'required|string',
            'duree' => 'required|string',
            'diplome' => 'required|string',
            'lieu' => 'required|string',

            'objectifs' => 'nullable|array',
            'objectifs.*' => 'string',

            // Image locale
            'icone' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

            // Image en ligne
            'icone_url' => 'nullable|url|max:500',

            'capacite' => 'nullable|integer|min:1',
            'is_active' => 'sometimes|boolean',
        ]);

        /*
        |--------------------------------------------------------------------------
        | GESTION DE L'IMAGE
        |--------------------------------------------------------------------------
        */

        $icone = null;

        // Si l'utilisateur choisit une image depuis son ordinateur
        if ($request->hasFile('icone')) {

            $icone = $request->file('icone')
                ->store('formations', 'public');

        // Sinon, s'il fournit une URL
        } elseif ($request->filled('icone_url')) {

            $icone = $request->icone_url;
        }

        /*
        |--------------------------------------------------------------------------
        | CREATION
        |--------------------------------------------------------------------------
        */

        $formation = Formation::create([
            'nom' => $request->nom,
            'resume' => $request->resume,
            'description' => $request->description,
            'duree' => $request->duree,
            'diplome' => $request->diplome,
            'lieu' => $request->lieu,
            'objectifs' => $request->objectifs,
            'icone' => $icone,
            'capacite' => $request->capacite,
            'is_active' => $request->input('is_active', true),
        ]);

        return response()->json([
            'message' => 'Formation créée avec succès.',
            'data' => $formation
        ], 201);
    }

    /**
     * DETAIL FORMATION
     */
    public function show(string $id)
    {
        $formation = Formation::findOrFail($id);

        return response()->json($formation);
    }

    /**
     * MODIFIER UNE FORMATION
     */
  /**
 * MODIFIER UNE FORMATION
 */
public function update(Request $request, string $id)
{
    // =====================================================
    // UTILISATEUR CONNECTÉ
    // =====================================================

    $user = Auth::guard('api')->user();

    if (
        !$user ||
        !in_array(
            $user->role,
            ['admin', 'gestionnaire']
        )
    ) {

        return response()->json([
            'message' => 'Accès refusé'
        ], 403);

    }


    // =====================================================
    // FORMATION
    // =====================================================

    $formation =
        Formation::findOrFail($id);


    // =====================================================
    // VALIDATION
    // =====================================================

    $request->validate([

        'nom' =>
            'sometimes|string|unique:formations,nom,' .
            $formation->id,

        'resume' =>
            'sometimes|string',

        'description' =>
            'sometimes|string',

        'duree' =>
            'sometimes|string',

        'diplome' =>
            'sometimes|string',

        'lieu' =>
            'sometimes|string',

        'objectifs' =>
            'sometimes|array',

        'objectifs.*' =>
            'string',

        'icone' =>
            'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',

        'icone_url' =>
            'nullable|url|max:500',

        'capacite' =>
            'nullable|integer|min:1',

        'is_active' =>
            'sometimes|boolean',

        'supprimer_icone' =>
            'sometimes|boolean',

    ]);


    // =====================================================
    // DONNÉES À MODIFIER
    // =====================================================

    $data = $request->only([

        'nom',

        'resume',

        'description',

        'duree',

        'diplome',

        'lieu',

        'objectifs',

        'capacite',

        'is_active',

    ]);


    // =====================================================
    // SUPPRESSION DE L'IMAGE
    // =====================================================

    if (
        $request->boolean(
            'supprimer_icone'
        )
    ) {

        // Si ancienne image locale
        if (
            $formation->icone &&
            !filter_var(
                $formation->icone,
                FILTER_VALIDATE_URL
            )
        ) {

            Storage::disk('public')
                ->delete(
                    $formation->icone
                );

        }


        // Supprimer de la BDD
        $data['icone'] = null;

    }


    // =====================================================
    // NOUVELLE IMAGE LOCALE
    // =====================================================

    if (
        $request->hasFile('icone')
    ) {

        // Supprimer ancienne image locale

        if (
            $formation->icone &&
            !filter_var(
                $formation->icone,
                FILTER_VALIDATE_URL
            )
        ) {

            Storage::disk('public')
                ->delete(
                    $formation->icone
                );

        }


        // Enregistrer nouvelle image

        $data['icone'] =
            $request
                ->file('icone')
                ->store(
                    'formations',
                    'public'
                );

    }


    // =====================================================
    // IMAGE EN LIGNE
    // =====================================================

    elseif (
        $request->filled('icone_url')
    ) {

        // Supprimer ancienne image locale

        if (
            $formation->icone &&
            !filter_var(
                $formation->icone,
                FILTER_VALIDATE_URL
            )
        ) {

            Storage::disk('public')
                ->delete(
                    $formation->icone
                );

        }


        // Enregistrer URL

        $data['icone'] =
            $request->icone_url;

    }


    // =====================================================
    // MODIFICATION
    // =====================================================

    $formation->update(
        $data
    );


    // =====================================================
    // RECHARGER
    // =====================================================

    $formation->refresh();


    // =====================================================
    // RÉPONSE
    // =====================================================

    return response()->json([

        'message' =>
            'Formation modifiée avec succès.',

        'data' =>
            $formation

    ]);

}

    /**
     * SUPPRIMER UNE FORMATION
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

        $formation = Formation::findOrFail($id);

        /*
        |--------------------------------------------------------------------------
        | SUPPRIMER L'IMAGE LOCALE
        |--------------------------------------------------------------------------
        */

        if (
            $formation->icone &&
            !filter_var($formation->icone, FILTER_VALIDATE_URL)
        ) {
            Storage::disk('public')->delete($formation->icone);
        }

        /*
        |--------------------------------------------------------------------------
        | SUPPRIMER LA FORMATION
        |--------------------------------------------------------------------------
        */

        $formation->delete();

        return response()->json([
            'message' => 'Formation supprimée avec succès.'
        ]);
    }
}