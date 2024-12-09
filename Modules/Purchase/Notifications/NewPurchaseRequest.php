<?php

namespace Modules\Purchase\Notifications;

use App\Models\Invoice;
use App\Models\EmailNotificationSetting;
use App\Http\Controllers\InvoiceController;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\SlackMessage;
use Modules\Purchase\Entities\PurchaseNotificationSetting;
use Modules\Purchase\Entities\PurchaseRequest;
use Modules\Purchase\Http\Controllers\PurchaseRequestController;
use NotificationChannels\OneSignal\OneSignalChannel;

class NewPurchaseRequest extends BaseNotification{
    private $request;
    private $emailSetting;

    public function __construct(PurchaseRequest $request){
        $this->request = $request;
        $this->company = $this->request->company;
        $this->emailSetting = PurchaseNotificationSetting::where('company_id', $this->company->id)->where('slug', 'new-purchase-request')->first();
    }

    public function via($notifiable){
        $via = ['database'];

        if ($this->emailSetting->send_email == 'yes' && $notifiable->email != '') {
            array_push($via, 'mail');
        }

        return $via;
    }

    public function toMail($notifiable){
        if ($this->request->vendor) {
            // For Sending pdf to email
            $PurchaseRequestController = new PurchaseRequestController();

            if ($pdfOption = $PurchaseRequestController->domPdfObjectForDownload($this->request->id)) {
                $pdf = $pdfOption['pdf'];
                $filename = $pdfOption['fileName'];

                $content = __('purchase::email.purchaseRequest.text') .' '. $this->request->vendor->currency->currency_symbol .''. $this->request->total;

                $newRequest = parent::build();
                $newRequest->subject(__('purchase::email.purchaseRequest.subject') . ' - ' . config('app.name') . '.')
                    ->markdown('mail.email', [
                        'content' => $content,
                        'themeColor' => $this->company->header_color,
                        'notifiableName' => $notifiable->name
                    ]);
                $newRequest->attachData($pdf->output(), $filename . '.pdf');

                return $newRequest;
            }
        }
    }

    public function toArray($notifiable){
        return [
            'id' => $this->request->id,
            'code' => $this->request->code
        ];
    }
}