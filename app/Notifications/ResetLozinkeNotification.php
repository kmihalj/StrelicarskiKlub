<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetLozinkeNotification extends ResetPassword
{
    /**
     * Get the reset password notification mail representation.
     *
     * @param mixed $notifiable
     */
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $expire = config('auth.passwords.' . config('auth.defaults.passwords') . '.expire');

        return (new MailMessage)
            ->subject('Reset lozinke')
            ->line('Zaprimili smo zahtjev za promjenu lozinke za vaš korisnički račun.')
            ->action('Postavi novu lozinku', $url)
            ->line('Poveznica za reset lozinke vrijedi ' . $expire . ' minuta.')
            ->line('Ako niste vi zatražili promjenu lozinke, nije potrebna dodatna akcija.');
    }
}
