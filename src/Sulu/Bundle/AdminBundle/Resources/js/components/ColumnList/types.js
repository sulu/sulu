// @flow
export const toolbarItemTypes = {
    Simple: 'simple',
    Dropdown: 'dropdown',
};

export type ButtonConfig = {
    icon: string,
    onClick: (string | number) => void,
};

export type DropdownOptionConfig = {
    label: string,
    onClick: (index: string | number) => void,
    disabled?: boolean,
};

type ToolbarItemBase = {
    index: number,
    icon: string,
    disabled?: boolean,
    skin?: 'blue',
};

export type DropdownProps = ToolbarItemBase & {
    options: Array<DropdownOptionConfig>,
};

export type SimpleProps = ToolbarItemBase & {
    onClick: (index: string | number) => void,
};

export type DropdownConfig = DropdownProps & { type: typeof toolbarItemTypes.Dropdown };

export type SimpleConfig = SimpleProps & { type: typeof toolbarItemTypes.Simple };

export type ToolbarItemConfig = DropdownConfig | SimpleConfig;
