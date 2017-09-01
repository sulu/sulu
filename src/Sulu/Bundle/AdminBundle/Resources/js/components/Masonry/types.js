// @flow
export type MasonryItem = {
    id: string | number,
    selected: boolean,
    icon?: string,
    onClick?: (itemId: string | number) => void,
    onSelectionChange?: (itemId: string | number, checked: boolean) => void,
};
