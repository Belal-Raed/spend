/** An iOS on/off switch for a checkbox. In the right-to-left page its knob starts on the right. */
export default function Switch({ name, defaultChecked = false }: { name: string; defaultChecked?: boolean }) {
    return (
        <input
            type="checkbox"
            name={name}
            value="1"
            role="switch"
            defaultChecked={defaultChecked}
            className="relative h-[31px] w-[51px] shrink-0 cursor-pointer appearance-none rounded-full bg-fill transition-colors duration-200 outline-none before:absolute before:top-0.5 before:right-0.5 before:size-[27px] before:rounded-full before:bg-white before:shadow-md before:transition-transform before:duration-200 checked:bg-switch-on checked:before:-translate-x-5 focus-visible:ring-2 focus-visible:ring-tint"
        />
    );
}
