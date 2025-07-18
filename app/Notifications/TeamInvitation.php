<?php

namespace App\Notifications;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    protected Invitation $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $organization = $this->invitation->organization;
        $invitedBy = $this->invitation->invitedBy;

        $message = (new MailMessage)
            ->subject("You're invited to join {$organization->name} on StatusPage")
            ->greeting("Hello {$this->invitation->name}!")
            ->line("You've been invited by {$invitedBy->name} to join {$organization->name} on StatusPage.")
            ->line("StatusPage helps organizations communicate service status, incidents, and maintenance to their users.")
            ->action('Accept Invitation', route('invitation.accept', $this->invitation->token))
            ->line("This invitation will expire in 7 days.")
            ->salutation("Best regards,\nThe StatusPage Team");

        if ($this->invitation->message) {
            $message->line("Personal message from {$invitedBy->name}:")
                   ->line("\"{$this->invitation->message}\"");
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'organization_name' => $this->invitation->organization->name,
            'invited_by' => $this->invitation->invitedBy->name,
            'role' => $this->invitation->role,
        ];
    }
}
