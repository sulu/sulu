// @flow
export type SelectMode = 'none' | 'single' | 'multiple';

export type SortOrder = 'asc' | 'desc';

export type ButtonConfig = {|
    icon: string,
    onClick: ?(rowId: string | number, index: number) => void,
|};

export type Skin = 'dark' | 'light';
