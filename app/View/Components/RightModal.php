<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RightModal extends Component{
    public function __construct(){
        //
    }

    public function render(){
        return view('components.right-modal');
    }
}