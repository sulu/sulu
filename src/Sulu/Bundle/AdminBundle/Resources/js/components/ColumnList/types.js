// @flow
export type ButtonConfig = {
    icon: string,
    onClick: (string | number) => void,
};

export type DropdownOptionConfig = {
    label: string,
    onClick: (index: string | number) => void,
};

type ToolbarItemBase = {
    index: number,
    icon: string,
    skin?: 'primary' | 'secondary',
};

export type DropdownProps = ToolbarItemBase & {
    options: Array<DropdownOptionConfig>,
};

export type SimpleProps = ToolbarItemBase & {
    onClick: (index: string | number) => void,
};

export type DropdownConfig = DropdownProps & { type: 'dropdown' };

export type SimpleConfig = SimpleProps & { type: 'simple'};

export type ToolbarItemConfig = DropdownConfig | SimpleConfig;
