<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    /**
     * Afficher toutes les notifications
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $notifications = $user->notifications()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Afficher uniquement les notifications non lues
     */
    public function unread(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $notifications = $user->unreadNotifications()
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'notifications' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

   
/**
 * Marquer une notification comme lue
 */
public function markAsRead(string $id): JsonResponse
{
    /** @var User $user */
    $user = Auth::guard('api')->user();

    $notification = DatabaseNotification::query()
        ->where('notifiable_type', User::class)
        ->where('notifiable_id', $user->getKey())
        ->where('id', $id)
        ->first();

    if (!$notification) {
        return response()->json([
            'success' => false,
            'message' => 'Notification introuvable.',
        ], 404);
    }

    $notification->markAsRead();

    return response()->json([
        'success' => true,
        'message' => 'Notification marquée comme lue.',
    ]);
}


    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été marquées comme lues.',
        ]);
    }

    /**
     * Supprimer une notification
     */
    public function destroy(string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        // Rechercher uniquement parmi les notifications de l'utilisateur connecté.
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()
            ->where('id', $id)
            ->first();

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification introuvable.',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée avec succès.',
        ]);
    }

    /**
     * Supprimer toutes les notifications de l'utilisateur connecté
     */
    public function destroyAll(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('api')->user();

        $user->notifications()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Toutes les notifications ont été supprimées.',
        ]);
    }
}