// @flow
export type SelectMode = 'none' | 'single' | 'multiple';

export type SortOrder = 'asc' | 'desc';

export type ButtonConfig = {
    icon: string,
    onClick: (string | number) => void,
};
