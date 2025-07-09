<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class MedicalTestResultNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $result;

    /**
     * Create a new notification instance.
     */
    public function __construct($result)
    {
        $this->result = $result;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = Storage::url($this->result->result_file);

        return (new MailMessage)
            ->subject('Medical Test Result Available')
            ->line('Your medical test result is ready.')
            ->line('Test: '.$this->result->appointment->medicalTest->test_name)
            ->line('Date: '.$this->result->appointment->appoint_date)
            ->action('Download Result', $url)
            ->line('Thank you for using our service!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'result_id' => $this->result->id,
            'test_name' => $this->result->appointment->medicalTest->test_name,
            'appoint_date' => $this->result->appointment->appoint_date,
            'result_file' => Storage::url($this->result->result_file),
        ];
    }
}
