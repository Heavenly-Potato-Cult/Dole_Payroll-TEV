<?php

namespace Modules\Payroll\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'plantilla_item_no' => $this->plantilla_item_no,
            'full_name'         => $this->full_name,
            'display_name'      => $this->display_name,
            'last_name'         => $this->last_name,
            'first_name'        => $this->first_name,
            'middle_name'       => $this->middle_name,
            'suffix'            => $this->suffix,
            'position_title'    => $this->position_title,
            'division'          => $this->whenLoaded('division', fn () => [
                'id'   => $this->division->id,
                'name' => $this->division->name,
                'code' => $this->division->code,
            ]),
            'psipop_office'     => $this->whenLoaded('psipopOffice', fn () => [
                'id'   => $this->psipopOffice->id,
                'name' => $this->psipopOffice->name,
            ]),
            'salary_grade'      => $this->salary_grade,
            'step'              => $this->step,
            'sit_year'          => $this->sit_year,
            'basic_salary'      => number_format($this->basic_salary, 2, '.', ''),
            'pera'              => number_format($this->pera, 2, '.', ''),
            'daily_rate'        => number_format($this->daily_rate, 4, '.', ''),
            'hourly_rate'       => number_format($this->hourly_rate, 4, '.', ''),
            'hire_date'         => $this->hire_date?->toDateString(),
            'status'            => $this->status,
            'tin'               => $this->tin,
            'gsis_no'           => $this->gsis_no,
            'pagibig_no'        => $this->pagibig_no,
            'philhealth_no'     => $this->philhealth_no,
            'sss_no'            => $this->sss_no,
            'created_at'        => $this->created_at?->toDateTimeString(),
            'updated_at'        => $this->updated_at?->toDateTimeString(),
        ];
    }
}
