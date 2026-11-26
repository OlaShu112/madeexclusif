
<?php

namespace App\Notifications;

use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $token,
        public ?int $storeId = null
    ) {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(
        object $notifiable
    ): MailMessage {

        $store = $this->storeId
            ? Store::query()
                ->whereKey($this->storeId)
                ->where('status', 'active')
                ->first()
            : null;

        $businessName =
            $store?->name ?? 'Made Exclusif';

        $frontendUrl = rtrim(
            config('app.frontend_url'),
            '/'
        );

        $storePath = $store && ! $store->is_main
            ? '/s/' . rawurlencode($store->slug)
            : '';

        $resetUrl =
            $frontendUrl .
            $storePath .
            '/reset-password?' .
            http_build_query([
                'token' => $this->token,
                'email' =>
                    $notifiable->getEmailForPasswordReset(),
            ]);

        return (new MailMessage)

            ->subject(
                "Reset Your Password | {$businessName}"
            )

            ->view(
                'emails.customer.password-reset',
                [
                    'user' => $notifiable,
                    'store' => $store,
                    'businessName' => $businessName,
                    'resetUrl' => $resetUrl,

                    'expiresIn' => config(
                        'auth.passwords.users.expire',
                        60
                    ),
                ]
            );
    }
}
