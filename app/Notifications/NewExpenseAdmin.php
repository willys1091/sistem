<?php

namespace App\Notifications;

use App\Models\EmailNotificationSetting;
use App\Models\Expense;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\OneSignal\OneSignalChannel;
use NotificationChannels\OneSignal\OneSignalMessage;

class NewExpenseAdmin extends BaseNotification{
    private $expense;
    private $emailSetting;

    public function __construct(Expense $expense){
        $this->expense = $expense;
        $this->company = $this->expense->company;
        $this->emailSetting = EmailNotificationSetting::where('company_id', $this->company->id)->where('slug', 'new-expenseadded-by-admin')->first();
    }

    public function via($notifiable){
        $via = ['database'];

        if ($this->emailSetting->send_email == 'yes' && $notifiable->email_notifications && $notifiable->email != '') {
            array_push($via, 'mail');
        }

        if ($this->emailSetting->send_slack == 'yes' && $this->company->slackSetting->status == 'active') {
            $this->slackUserNameCheck($notifiable) ? array_push($via, 'slack') : null;
        }

        if ($this->emailSetting->send_push == 'yes') {
            array_push($via, OneSignalChannel::class);
        }

        return $via;
    }

    public function toMail($notifiable): MailMessage{
        $build = parent::build();
        $url = route('expenses.show', $this->expense->id);
        $url = getDomainSpecificUrl($url, $this->company);

        if ($this->expense->status == 'approved') {
            $subject = __('email.newExpense.newSubject');
        }
        else {
            $subject = __('email.newExpense.subject');
        }

        $content = $subject . '<br>' . __('app.status') . ': ' . $this->expense->status . '<br>' . __('app.employee') . ': ' . $this->expense->user->name . '<br>' . __('modules.expenses.itemName') . ': ' . $this->expense->item_name . '<br>' . __('app.price') . ': ' . currency_format($this->expense->price, $this->expense->currency->id);

        return $build
            ->subject($subject . ' - ' . config('app.name'))
            ->markdown('mail.email', [
                'url' => $url,
                'content' => $content,
                'themeColor' => $this->company->header_color,
                'actionText' => __('email.newExpense.action'),
                'notifiableName' => $notifiable->name
            ]);
    }

    public function toArray($notifiable){
        return [
            'id' => $this->expense->id,
            'user_id' => $notifiable->id,
            'item_name' => $this->expense->item_name
        ];
    }

    public function toSlack($notifiable){
        return $this->slackBuild($notifiable)
            ->content(__('email.newExpense.subject') . ' - ' . $this->expense->item_name . ' - ' . currency_format($this->expense->price, $this->expense->currency->id) . "\n" . url('/login'));

    }

    public function toOneSignal($notifiable){
        return OneSignalMessage::create()
            ->setSubject(__('email.newExpense.subject'))
            ->setBody($this->expense->item_name . ' by ' . $this->expense->user->name);
    }
}