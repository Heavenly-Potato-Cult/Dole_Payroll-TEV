<?php
namespace Modules\Payroll\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialPayrollRequest extends FormRequest {
    public function authorize() { return true; }
    
    public function rules() {
        return [
            'payroll_type' => 'required|in:newly_hired,transferee,others',
            'employee_id' => 'required|exists:employees,id',
            'effectivity_date' => 'required|date',
            'cutoff_start' => 'required|date',
            'cutoff_end' => 'required|date|after_or_equal:cutoff_start',
            'lwop_days' => 'nullable|integer|min:0|max:22',
            'remarks' => 'nullable|string|max:500',
            'deduction_gsis_percent' => 'nullable|numeric|min:0|max:100',
        ];
    }
    
    public function messages() {
        return [
            'payroll_type.required' => 'Please select a payroll type.',
            'payroll_type.in' => 'Invalid payroll type selected.',
            'employee_id.required' => 'Please select an employee.',
            'effectivity_date.required' => 'Effectivity date is required.',
            'cutoff_start.required' => 'Cut-off start date is required.',
            'cutoff_end.required' => 'Cut-off end date is required.',
            'cutoff_end.after_or_equal' => 'Cut-off end must be after or equal to cut-off start.',
        ];
    }
}
