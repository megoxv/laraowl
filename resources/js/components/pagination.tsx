import { Link } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-react';

export function Pagination({ links, meta }: { links: any; meta: any }) {
    if (!meta || meta.last_page <= 1) {
        return null;
    }

    return (
        <div className="flex items-center justify-between gap-4 border-t border-border bg-white/[0.01] p-4">
            <div className="text-[10px] font-bold tracking-widest text-muted-foreground uppercase">
                Showing {meta.from} to {meta.to} of {meta.total} results
            </div>

            <div className="flex items-center gap-1">
                {/* First Page */}
                <Link
                    href={links.first}
                    className={`rounded p-1.5 transition-colors hover:bg-white/10 ${!links.prev ? 'cursor-not-allowed opacity-30' : ''}`}
                >
                    <ChevronsLeft className="h-4 w-4" />
                </Link>

                {/* Previous Page */}
                <Link
                    href={links.prev}
                    className={`rounded p-1.5 transition-colors hover:bg-white/10 ${!links.prev ? 'cursor-not-allowed opacity-30' : ''}`}
                >
                    <ChevronLeft className="h-4 w-4" />
                </Link>

                {/* Page Numbers */}
                <div className="mx-2 flex items-center gap-1">
                    {meta.links
                        .filter((l: any) => !isNaN(Number(l.label)))
                        .map((link: any) => (
                            <Link
                                key={link.label}
                                href={link.url}
                                className={`flex h-7 min-w-[28px] items-center justify-center rounded text-xs font-bold transition-all ${link.active ? 'border border-border bg-white/10 text-foreground' : 'text-gray-500 hover:bg-muted hover:text-foreground'}`}
                            >
                                {link.label}
                            </Link>
                        ))}
                </div>

                {/* Next Page */}
                <Link
                    href={links.next}
                    className={`rounded p-1.5 transition-colors hover:bg-white/10 ${!links.next ? 'cursor-not-allowed opacity-30' : ''}`}
                >
                    <ChevronRight className="h-4 w-4" />
                </Link>

                {/* Last Page */}
                <Link
                    href={links.last}
                    className={`rounded p-1.5 transition-colors hover:bg-white/10 ${!links.next ? 'cursor-not-allowed opacity-30' : ''}`}
                >
                    <ChevronsRight className="h-4 w-4" />
                </Link>
            </div>
        </div>
    );
}
