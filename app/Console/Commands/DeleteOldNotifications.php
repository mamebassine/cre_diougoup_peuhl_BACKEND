<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DeleteOldNotifications extends Command
{
    /**
     * Nom de la commande Artisan.
     */
    protected $signature = 'notifications:delete-old';

    /**
     * Description de la commande.
     */
    protected $description = 'Supprimer les notifications datant de plus de 30 jours';

    /**
     * Exécuter la commande.
     */
    public function handle(): int
    {
        $dateLimite = now()->subDays(30);

        $nombreSupprime = DB::table('notifications')
            ->where('created_at', '<', $dateLimite)
            ->delete();

        $this->info(
            $nombreSupprime . ' notification(s) ancienne(s) supprimée(s).'
        );

        return self::SUCCESS;
    }
}
