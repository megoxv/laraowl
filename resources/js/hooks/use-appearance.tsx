export type ResolvedAppearance = 'light' | 'dark';
export type Appearance = ResolvedAppearance | 'system';

export type UseAppearanceReturn = {
    readonly appearance: Appearance;
    readonly resolvedAppearance: ResolvedAppearance;
    readonly updateAppearance: (mode: Appearance) => void;
};

export function initializeTheme(): void {
    if (typeof document !== 'undefined') {
        document.documentElement.classList.add('dark');
        document.documentElement.style.colorScheme = 'dark';
    }
}

export function useAppearance(): UseAppearanceReturn {
    return {
        appearance: 'dark',
        resolvedAppearance: 'dark',
        updateAppearance: () => {},
    } as const;
}
