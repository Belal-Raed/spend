<button {{ $attributes->merge(['type' => 'submit', 'class' => 'cursor-pointer rounded-lg bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-700']) }}>
    {{ $slot }}
</button>
