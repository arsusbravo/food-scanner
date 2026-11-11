<?php

namespace App\Http\Requests;

use App\Models\Receipt;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->route('receipt')->user_id === $this->user()->id;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_name' => ['nullable', 'string', 'max:150'],
            'supplier_vat_id' => ['nullable', 'string', 'max:50'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'currency' => ['required', 'string', 'size:3'],
            'net_total' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'vat_total' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'gross_total' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'confirm' => ['boolean'],
            'lines' => ['present', 'array', 'max:150'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'lines.*.unit' => ['nullable', Rule::in(['pc', 'kg', 'g', 'l', 'ml', 'cl'])],
            'lines.*.unit_price' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'lines.*.line_total' => ['nullable', 'numeric', 'min:-9999999', 'max:9999999'],
            'lines.*.category' => ['required', Rule::in(Receipt::LINE_CATEGORIES)],
        ];
    }
}
