<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\User;
use App\Models\EmployeeDetails;
use App\Models\ExpenseApproval;
use App\Models\ExpenseAct;
use App\Models\SettlementApproval;
use App\Models\SettlementAct;
use App\Models\ReimbursementApproval;
use App\Models\ReimbursementAct;
use App\Models\PettycashApproval;
use App\Models\PettycashAct;
use Modules\Purchase\Entities\PurchaseRequestApproval;
use Modules\Purchase\Entities\PurchaseRequestAct;

trait General{
    static function numerator($num){
        $num    = ( string ) ( ( int ) $num );
        if( ( int ) ( $num ) && ctype_digit( $num ) ){
            $words  = array( );
            $num    = str_replace( array( ',' , ' ' ) , '' , trim( $num ) );
            $list1  = array('','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen');
            $list2  = array('','ten','twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety','hundred');
            $list3  = array('','thousand','million','billion','trillion','quadrillion','quintillion','sextillion','septillion','octillion','nonillion','decillion','undecillion','duodecillion','tredecillion','quattuordecillion','quindecillion','sexdecillion','septendecillion','octodecillion','novemdecillion','vigintillion');
            $num_length = strlen( $num );
            $levels = ( int ) ( ( $num_length + 2 ) / 3 );
            $max_length = $levels * 3;
            $num    = substr( '00'.$num , -$max_length );
            $num_levels = str_split( $num , 3 );
             
            foreach( $num_levels as $num_part ){
                $levels--;
                $hundreds   = ( int ) ( $num_part / 100 );
                $hundreds   = ( $hundreds ? ' ' . $list1[$hundreds] . ' Hundred' . ( $hundreds == 1 ? '' : 's' ) . ' ' : '' );
                $tens       = ( int ) ( $num_part % 100 );
                $singles    = '';
                 
                if( $tens < 20 ) { $tens = ( $tens ? ' ' . $list1[$tens] . ' ' : '' ); } else { $tens = ( int ) ( $tens / 10 ); $tens = ' ' . $list2[$tens] . ' '; $singles = ( int ) ( $num_part % 10 ); $singles = ' ' . $list1[$singles] . ' '; } $words[] = $hundreds . $tens . $singles . ( ( $levels && ( int ) ( $num_part ) ) ? ' ' . $list3[$levels] . ' ' : '' ); } $commas = count( $words ); if( $commas > 1 ){
                $commas = $commas - 1;
            }
             
            $words  = implode( ', ' , $words );
        
            $words  = trim( str_replace( ' ,' , ',' , ucwords( $words ) )  , ', ' );
            if( $commas ){
                $words  = str_replace( ',' , ' and' , $words );
            }
            return $words;
        }else if( ! ( ( int ) $num ) ){
            return 'Zero';
        }
        return '';
    }

    public function getUserApproval($type,$id){
        if($type=='author'){
            return $id;
        }elseif($type=='hod'){
            return EmployeeDetails::whereUser_id($id)->value('reporting_to');
            // $emp = EmployeeDetails::whereUser_id($id)->first();
            // return EmployeeDetails::whereCompany_idAndDepartment_idAndDesignation_id($emp->company_id,$emp->department_id,'1')->value('user_id');
        }
    }

    public function getApprovalAct($modul,$id,$stateid){
        if($modul=='expense'){
            $apvid = ExpenseApproval::whereHeader_idAndState_id($id,$stateid)->value('id');
            $btn = ExpenseAct::whereApproval_id($apvid)->get();
        }elseif($modul=='settlement'){
            $apvid = SettlementApproval::whereHeader_idAndState_id($id,$stateid)->value('id');
            $btn = SettlementAct::whereApproval_id($apvid)->get();
        }elseif($modul=='reimbursement'){
            $apvid = ReimbursementApproval::whereHeader_idAndState_id($id,$stateid)->value('id');
            $btn = ReimbursementAct::whereApproval_id($apvid)->get();
        }elseif($modul=='pettycash'){
            $apvid = PettycashApproval::whereHeader_idAndState_id($id,$stateid)->value('id');
            $btn = PettycashAct::whereApproval_id($apvid)->get();
        }elseif($modul=='purchase_request'){
            $apvid = PurchaseRequestApproval::whereHeader_idAndState_id($id,$stateid)->value('id');
            $btn = PurchaseRequestAct::whereApproval_id($apvid)->get();
        }elseif($modul=='quotation'){
            $apvid = QuotationApproval::whereHeader_idAndState_id($id,$stateid)->value('id');
            $btn = QuotationAct::whereApproval_id($apvid)->get();
        }
        return $btn;
    }
}