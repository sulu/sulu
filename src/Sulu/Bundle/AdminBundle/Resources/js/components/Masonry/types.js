// @flow
export type MasonryItem = {
    id: string | number,
    selected: boolean,
    onClick?: (itemId: string | number) => void,
    onSelectionChange?: (itemId: string | number, checked: boolean) => void,
};
