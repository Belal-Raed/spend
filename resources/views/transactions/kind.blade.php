{{-- What a transaction is, in a few words: its type, and its category or the person it is with. --}}
@if ($transaction->isDebt())
    دين {{ $transaction->debt_direction->label() }} · {{ $transaction->counterparty }}
@elseif ($transaction->isRepayment())
    سداد دين · {{ $transaction->counterparty }}
@else
    {{ $transaction->type->label() }} · {{ $transaction->category?->name ?? 'بدون تصنيف' }}@if ($transaction->counterparty) · {{ $transaction->counterparty }}@endif
@endif
