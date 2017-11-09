// @flow
export type ItemButtonConfig = {
    icon: string,
    onClick: (string | number) => void,
};

export type ToolbarDropdownOptionConfig = {
    label: string,
    onClick: (index?: string | number) => void,
};

type ToolbarBase = {
    index?: number,
    icon: string,
    skin?: 'primary' | 'secondary',
};

export type ToolbarDropdown = ToolbarBase & {
    options: Array<ToolbarDropdownOptionConfig>,
};

export type ToolbarButton = ToolbarBase & {
    onClick: (index?: string | number) => void,
};

export type ToolbarDropdownConfig = ToolbarDropdown & { type: 'dropdown' };

export type ToolbarButtonConfig = ToolbarButton & { type: 'button'};

export type ToolbarItemConfig = ToolbarDropdownConfig | ToolbarButtonConfig;
