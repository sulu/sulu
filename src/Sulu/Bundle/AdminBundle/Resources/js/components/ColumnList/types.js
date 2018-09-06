// @flow
export type ItemButtonConfig = {
    icon: string,
    onClick: (string | number) => void,
};

export type ToolbarDropdownOptionConfig = {
    disabled?: boolean,
    label: string,
    onClick: () => void,
};

type ToolbarBase = {
    icon: string,
    skin?: 'primary' | 'secondary',
};

export type ToolbarDropdown = ToolbarBase & {
    options: Array<ToolbarDropdownOptionConfig>,
};

export type ToolbarButton = ToolbarBase & {
    onClick: () => void,
};

export type ToolbarDropdownConfig = ToolbarDropdown & { type: 'dropdown' };

export type ToolbarButtonConfig = ToolbarButton & { type: 'button'};

export type ToolbarItemConfig = ToolbarDropdownConfig | ToolbarButtonConfig;
