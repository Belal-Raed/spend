<x-page title="الديون">
    @error('amount')
        <p role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm text-red-800">{{ $message }}</p>
    @enderror

    <x-debt-list title="ديون عليّ" :debts="$debtsOwedByMe" empty="ما عليك ديون مفتوحة." actions />
    <x-debt-list title="ديون إلي" :debts="$debtsOwedToMe" empty="ما إلك ديون مفتوحة." actions />
</x-page>
