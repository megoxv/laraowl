import type { Auth } from './auth';
import type { Team } from './teams';

export type * from './auth';
export type * from './navigation';
export type * from './teams';
export type * from './ui';

export interface SharedData {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    currentTeam: Team | null;
    teams: Team[];
    [key: string]: unknown;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & SharedData;
