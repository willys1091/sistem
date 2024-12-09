<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\TicketType\StoreTicketType;
use App\Http\Requests\TicketType\UpdateTicketType;
use App\Models\TicketType;

class TicketTypeController extends AccountBaseController{
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'app.menu.ticketTypes';
        $this->activeSettingMenu = 'ticket_types';
    }

    public function create(){
        $this->ticketTypes = TicketType::all();
        return view('ticket-settings.create-ticket-type-modal', $this->data);
    }

  
    public function store(StoreTicketType $request){
        $type = new TicketType();
        $type->type = $request->type;
        $type->save();
        $allTypes = TicketType::all();
        $select = '';
        foreach($allTypes as $type){
            $select .= '<option value="'.$type->id.'">'.$type->type.'</option>';
        }
        return Reply::successWithData(__('messages.recordSaved'), ['optionData' => $select]);
    }

    public function edit($id){
        $this->type = TicketType::findOrFail($id);
        return view('ticket-settings.edit-ticket-type-modal', $this->data);
    }

    public function update(UpdateTicketType $request, $id){
        $type = TicketType::findOrFail($id);
        $type->type = $request->type;
        $type->save();
        return Reply::success(__('messages.updateSuccess'));
    }

    public function destroy($id){
        TicketType::destroy($id);
        return Reply::success(__('messages.deleteSuccess'));
    }
}