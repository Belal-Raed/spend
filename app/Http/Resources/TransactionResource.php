<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * The category name is included only when the category relation was loaded.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'amount' => $this->amount,
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', fn (): ?string => $this->category?->name),
            'counterparty' => $this->counterparty,
            'debt_direction' => $this->debt_direction?->value,
            'debt_direction_label' => $this->debt_direction?->label(),
            'note' => $this->note,
            'occurred_on' => $this->occurred_on->toDateString(),
            'is_debt' => $this->isDebt(),
            'is_repayment' => $this->isRepayment(),
            'is_settled' => $this->isSettled(),
            'adds_to_balance' => $this->addsToBalance(),
        ];
    }
}
