{{--
    An iOS action sheet: a list of choices that slides up from the bottom, with
    a cancel button under it. Open it with a [data-sheet-open="{id}"] button.
    The slot holds <x-sheet-action> choices.
--}}
@props(['id', 'title' => null, 'message' => null])

<dialog id="{{ $id }}" class="sheet" aria-label="{{ $title ?? $message }}">
    <div class="flex flex-col gap-2">
        <div class="flex flex-col overflow-hidden rounded-[14px] bg-raised/95 text-center backdrop-blur-xl [&>*+*]:border-t-[0.5px] [&>*+*]:border-separator">
            @if ($title || $message)
                <div class="flex flex-col gap-0.5 px-4 py-3.5">
                    @if ($title)
                        <p class="text-[13px] font-semibold text-muted">{{ $title }}</p>
                    @endif

                    @if ($message)
                        <p class="text-[13px] text-muted">{{ $message }}</p>
                    @endif
                </div>
            @endif

            {{ $slot }}
        </div>

        <button type="button" data-sheet-close class="cursor-pointer rounded-[14px] bg-raised py-4 text-[19px] font-semibold text-tint active:bg-pressed">إلغاء</button>
    </div>
</dialog>
