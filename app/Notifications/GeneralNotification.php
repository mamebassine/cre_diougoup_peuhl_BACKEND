<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GeneralNotification extends Notification
{
    use Queueable;

    protected string $titre;
    protected string $message;
    protected string $type;
    protected ?string $statut;
    protected ?object $inscription;

    public function __construct(
        string $titre,
        string $message,
        string $type = 'info',
        ?string $statut = null,
        ?object $inscription = null
    ) {
        $this->titre = $titre;
        $this->message = $message;
        $this->type = $type;
        $this->statut = $statut;
        $this->inscription = $inscription;
    }

    public function via(object $notifiable): array
    {
        $canaux = ['database'];

        if (!empty($notifiable->routeNotificationFor('mail'))) {
            $canaux[] = 'mail';
        }

        return $canaux;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'titre' => $this->titre,
            'message' => $this->message,
            'type' => $this->type,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $sujet = $this->titre;
        $messagePrincipal = $this->message;

        // Récupérer le profil de l'apprenant.
        $apprenant = $notifiable->apprenant ?? null;

        // Récupérer le nom complet.
        $nomComplet = trim(
            ($notifiable->prenom ?? '') . ' ' .
            ($notifiable->nom ?? '')
        );

        if ($nomComplet === '') {
            $nomComplet = $notifiable->name
                ?? $notifiable->nom_complet
                ?? 'cher apprenant';
        }

        // Récupérer la formation liée à l'inscription.
        $formation = $this->inscription?->formation;

        $nomFormation = null;

        if ($formation) {
            $nomFormation = $formation->nom
                ?? $formation->titre
                ?? $formation->intitule
                ?? null;
        }

        // Personnaliser le sujet et le message selon le statut.
        if ($this->type === 'inscription') {
            switch ($this->statut) {
                case 'Valide':
                    $sujet = 'Félicitations ! Votre inscription est acceptée';

                    $messagePrincipal =
                        'Félicitations ! Votre inscription au CRE de Diougoup Peulh est acceptée.';
                    break;

                case 'Refuse':
                    $sujet = 'Information concernant votre inscription';

                    $messagePrincipal =
                        'Nous vous remercions de votre intérêt pour le CRE de Diougoup Peulh. Votre demande n’a malheureusement pas été acceptée. Pour toute information complémentaire, nous vous invitons à contacter le centre.';
                    break;

                case 'En attente':
                    $sujet = 'Votre inscription est en cours de traitement';

                    $messagePrincipal =
                        'Votre demande d’inscription est en cours de traitement. Nous vous informerons dès qu’une décision sera prise. Merci pour votre confiance.';
                    break;
            }
        }

        // Message complémentaire pour une inscription acceptée.
        $messageAccueil = null;

        if (
            $this->type === 'inscription'
            && $this->statut === 'Valide'
        ) {
            $messageAccueil = [
                'Au CRE, nous croyons en votre potentiel et vous accompagnons vers l’apprentissage, l’innovation et la réussite.',
                'Nous vous invitons à vous présenter au centre pour préparer le démarrage de la prochaine cohorte.',
                'Bienvenue dans une aventure où vos ambitions prennent vie !',
            ];
        }

        // Matricule de l'apprenant.
        $matricule = $apprenant?->matricule;

        // Utiliser le modèle HTML personnalisé.
        return (new MailMessage)
            ->subject($sujet)
            ->view('emails.notifications.general', [
                'titre' => $sujet,
                'nomComplet' => $nomComplet,
                'messagePrincipal' => $messagePrincipal,
                'type' => $this->type,
                'statut' => $this->statut,
                'nomFormation' => $nomFormation,
                'matricule' => $matricule,
                'messageAccueil' => $messageAccueil,
                'adresseCentre' => 'Boston, Sénégal',
                'telephoneCentre' => '+221 77 106 51 56',
                'emailCentre' => 'bassinan13@gmail.com',
            ]);
    }
}