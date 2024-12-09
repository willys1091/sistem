<?php

namespace App\Observers;

use App\Models\ClientDocument;

class ClientDocumentObserver{
    public function saving(ClientDocument $clientDocs){
        if (user()) {
            $clientDocs->last_updated_by = user()->id;
        }
    }

    public function creating(ClientDocument $clientDocs){
        if (user()) {
            $clientDocs->added_by = user()->id;
        }

        if (company()) {
            $clientDocs->company_id = company()->id;
        }
    }
}