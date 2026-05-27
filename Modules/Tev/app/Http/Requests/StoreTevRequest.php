<?php

namespace Modules\Tev\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTevRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Role/ownership checks handled in controller
    }

    /**
     * Normalize time values before validation.
     * The browser time input may submit "11:00 AM" / "08:00 PM" depending on
     * locale settings. strtotime() converts any recognizable format to H:i.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('lines')) {
            $lines = collect($this->lines)->map(function ($line) {
                if (!empty($line['departure_time'])) {
                    $parsed = strtotime($line['departure_time']);
                    $line['departure_time'] = $parsed !== false ? date('H:i', $parsed) : null;
                }
                if (!empty($line['arrival_time'])) {
                    $parsed = strtotime($line['arrival_time']);
                    $line['arrival_time'] = $parsed !== false ? date('H:i', $parsed) : null;
                }
                return $line;
            })->all();

            $this->merge(['lines' => $lines]);
        }
    }

    public function rules(): array
    {
        $track = $this->input('track');
        
        $rules = [
            'office_order_id'             => ['required', 'integer', 'exists:office_orders,id'],
            'track'                       => ['required', 'in:cash_advance,reimbursement'],
            'purpose'                     => ['required', 'string', 'max:500'],
            'destination'                 => ['required', 'string', 'max:255'],
            'travel_type'                 => ['required', 'in:local,regional,national'],
            'travel_date_start'           => ['required', 'date'],
            'travel_date_end'             => ['required', 'date', 'after_or_equal:travel_date_start'],
            'remarks'                     => ['nullable', 'string', 'max:1000'],

            // Supporting documents (optional)
            'documents'                   => ['nullable', 'array', 'max:5'],
            'documents.*'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],

            // Itinerary lines
            'lines'                       => ['required', 'array', 'min:1'],
            'lines.*.travel_date'         => ['required', 'date'],
            'lines.*.origin'              => ['required', 'string', 'max:255'],
            'lines.*.destination'         => ['required', 'string', 'max:255'],
            'lines.*.departure_time'      => ['nullable', 'date_format:H:i'],
            'lines.*.arrival_time'        => ['nullable', 'date_format:H:i'],
            'lines.*.mode_of_transport'   => ['required', 'string', 'max:50'],
            'lines.*.transportation_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.per_diem_amount'     => ['required', 'numeric', 'min:0'],
            'lines.*.is_half_day'         => ['nullable', 'boolean'],
        ];

        // Cash Advance requires additional validation for liquidation documents
        if ($track === 'cash_advance') {
            $rules['has_receipt'] = ['required', 'boolean'];
            $rules['has_boarding_pass'] = ['required', 'boolean'];
            $rules['has_cert_complete'] = ['required', 'boolean'];
            $rules['liquidation_remarks'] = ['nullable', 'string', 'max:1000'];
        }

        // Reimbursement requires additional validation for supporting documents
        if ($track === 'reimbursement') {
            $rules['has_proof_payment'] = ['required', 'boolean'];
            $rules['has_travel_cert'] = ['required', 'boolean'];
            $rules['reimbursement_remarks'] = ['nullable', 'string', 'max:1000'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'track.in'                            => 'Track must be cash_advance or reimbursement.',
            'travel_type.in'                      => 'Travel type must be local, regional, or national.',
            'travel_date_end.after_or_equal'      => 'End date must be on or after the start date.',
            'lines.required'                      => 'At least one itinerary line is required.',
            'lines.min'                           => 'At least one itinerary line is required.',
            'lines.*.travel_date.required'        => 'Each line must have a travel date.',
            'lines.*.origin.required'             => 'Each line must have a From (Origin).',
            'lines.*.destination.required'        => 'Each line must have a To (Destination).',
            'lines.*.mode_of_transport.required'  => 'Each line must have a mode of transport.',
            'lines.*.transportation_cost.required' => 'Transportation cost is required for each line.',
            'lines.*.per_diem_amount.required'    => 'Per diem amount is required for each line.',
            'has_receipt.required'               => 'Receipt status is required for Cash Advance.',
            'has_boarding_pass.required'          => 'Boarding pass status is required for Cash Advance.',
            'has_cert_complete.required'          => 'Certificate of completion status is required for Cash Advance.',
            'has_proof_payment.required'          => 'Proof of payment status is required for Reimbursement.',
            'has_travel_cert.required'            => 'Travel completion certificate status is required for Reimbursement.',
        ];
    }
}