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

export type DropdownProps = {
    index: number,
    icon: string,
    options: Array<DropdownOptionConfig>,
    disabled?: boolean,
};

export type SimpleProps = {
    index: number,
    icon: string,
    onClick: (index: string | number) => void,
    disabled?: boolean,
};

export type DropdownConfig = DropdownProps & { type: typeof toolbarItemTypes.Dropdown };

export type SimpleConfig = SimpleProps & { type: typeof toolbarItemTypes.Simple };

export type ToolbarItemConfig = DropdownConfig | SimpleConfig;
