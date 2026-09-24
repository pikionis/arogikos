<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Invitation email.
 *
 * Deliberately NOT ShouldQueue: production runs QUEUE_CONNECTION=sync with no
 * worker, so a queued notification would sit in `jobs` forever and nobody
 * would ever get invited.
 */
class PanelInvitation extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Invitation $invitation,
        private readonly string $acceptUrl,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $org = setting('org_name', 'Αρωγή');
        $inviter = $this->invitation->inviter?->name;

        return (new MailMessage)
            ->subject('Πρόσκληση στη διαχείριση του '.$org)
            ->greeting('Γεια σας '.$this->invitation->name.',')
            ->line($inviter
                ? "Ο/Η {$inviter} σας προσκάλεσε στη διαχείριση του site «{$org}»."
                : "Λάβατε πρόσκληση στη διαχείριση του site «{$org}».")
            ->line('Ο ρόλος σας θα είναι: **'.$this->invitation->role->label().'** — '.$this->invitation->role->description())
            ->action('Ορισμός κωδικού και είσοδος', $this->acceptUrl)
            ->line('Ο σύνδεσμος λήγει σε '.Invitation::EXPIRES_AFTER_DAYS.' ημέρες και χρησιμοποιείται μία φορά.')
            ->line('Αν δεν περιμένατε αυτή την πρόσκληση, αγνοήστε το email.')
            ->salutation('— '.$org);
    }
}
