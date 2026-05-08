import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function formatMicroSeconds(us: any) {
    if (us === undefined || us === null) {
        return '0ms';
    }

    // Handle array case from Recharts ValueType
    if (Array.isArray(us)) {
        us = us[0];
    }

    const value = Number(us);

    if (isNaN(value)) {
        return '0ms';
    }

    const ms = value / 1000;

    if (ms < 1) {
        return `${value.toFixed(0)}µs`;
    }

    if (ms < 1000) {
        return `${ms.toFixed(1)}ms`;
    }

    if (ms < 60000) {
        return `${(ms / 1000).toFixed(1)}s`;
    }

    if (ms < 3600000) {
        return `${(ms / 60000).toFixed(1)}m`;
    }

    return `${(ms / 3600000).toFixed(1)}h`;
}

export function formatCompactNumber(num: any) {
    if (num === undefined || num === null) {
        return '0';
    }

    // Handle array case from Recharts ValueType
    if (Array.isArray(num)) {
        num = num[0];
    }

    const value = Number(num);

    if (isNaN(value)) {
        return '0';
    }

    if (value < 1000) {
        return value.toString();
    }

    if (value < 1000000) {
        return `${(value / 1000).toFixed(1)}k`;
    }

    return `${(value / 1000000).toFixed(1)}m`;
}

export function formatValue(value: any): string {
    if (value === null || value === undefined) {
        return '';
    }

    if (typeof value === 'object') {
        try {
            return JSON.stringify(value);
        } catch {
            return '[Complex Object]';
        }
    }

    return String(value);
}
