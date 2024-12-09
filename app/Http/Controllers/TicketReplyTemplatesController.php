<?php

namespace App\Http\Controllers;

use App\Helper\Reply;
use App\Http\Requests\TicketReplyTemplate\StoreTemplate;
use App\Http\Requests\TicketReplyTemplate\UpdateTemplate;
use App\Models\TicketReplyTemplate;
use Illuminate\Http\Request;

class TicketReplyTemplatesController extends AccountBaseController{
    public function __construct(){
        parent::__construct();
        $this->pageTitle = 'app.menu.replyTemplates';
        $this->activeSettingMenu = 'ticket_reply_templates';
    }

    public function create(){
        return view('ticket-settings.create-ticket-reply-template-modal');
    }

    public function store(StoreTemplate $request){
        $template = new TicketReplyTemplate();
        $template->reply_heading = trim_editor($request->reply_heading);
        $template->reply_text = $request->description;
        $template->save();

        return Reply::success(__('messages.recordSaved'));
    }

    public function edit($id){
        $this->template = TicketReplyTemplate::findOrFail($id);
        return view('ticket-settings.edit-ticket-reply-template-modal', $this->data);
    }

    public function update(UpdateTemplate $request, $id){
        $template = TicketReplyTemplate::findOrFail($id);
        $template->reply_heading = $request->reply_heading;
        $template->reply_text = $request->description;
        $template->save();

        return Reply::success(__('messages.templateUpdateSuccess'));
    }

    public function destroy($id){
        TicketReplyTemplate::destroy($id);
        return Reply::success(__('messages.deleteSuccess'));
    }

    public function fetchTemplate(Request $request){
        $templateId = $request->templateId;
        $template = TicketReplyTemplate::findOrFail($templateId);
        return Reply::dataOnly(['replyText' => $template->reply_text, 'status' => 'success']);
    }
}