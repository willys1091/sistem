<?php

namespace App\Http\Requests\Milestone;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Project;

class StoreMilestone extends FormRequest{
    public function authorize(){
        return true;
    }

    public function rules(){
        $project = Project::findOrFail(request()->project_id);
        $setting = company();
        $rules = [
            'project_id' => 'required',
            'milestone_title' => 'required',
            'summary' => 'required'
        ];
        if (request()->has('project_id') && request()->project_id != 'all' && request()->project_id != '') {
            $startDate = $project->start_date->format($setting->date_format);
            $endDate = $project->deadline->format($setting->date_format);
            if ($this->end_date !== null) {
                $rules['start_date'] = 'required|date_format:"' . $setting->date_format . '"|after_or_equal:' . $startDate;
            }
            if ($this->start_date !== null) {
                $rules['end_date'] = 'required|date_format:"' . $setting->date_format . '"|before_or_equal:' . $endDate;
            }
        }else{
            if ($this->end_date !== null) {
                $rules['start_date'] = 'required';
            }
            if ($this->start_date !== null) {
                $rules['end_date'] = 'required';
            }
        }
        
        if ($this->start_date > $this->end_date) {
            $rules['end_date'] = 'after_or_equal:start_date';
        }
        
        if ($this->cost != '' && $this->cost > 0) {
            $rules['currency_id'] = 'required';
        }
        return $rules;
    }
}