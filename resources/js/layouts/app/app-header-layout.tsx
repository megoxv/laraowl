import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import type { AppLayoutProps } from '@/types';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    return (
        <AppShell variant="header">
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent variant="header" className="min-h-screen bg-[#050505]">
                <div className="mx-auto w-full max-w-7xl animate-in space-y-8 p-6 duration-500 fade-in md:p-8">
                    {children}
                </div>
            </AppContent>
        </AppShell>
    );
}
