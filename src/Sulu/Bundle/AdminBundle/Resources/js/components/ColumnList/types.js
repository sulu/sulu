// @flow
export type ItemButtonConfig = {
    icon: string,
    onClick: (string | number) => void,
};

export type ToolbarDropdownOptionConfig = {
    isDisabled?: (columnIndex?: string | number) => boolean,
    label: string,
    onClick: (columnIndex?: string | number) => void,
};

type ToolbarBase = {
    columnIndex?: number,
    icon: string,
    skin?: 'primary' | 'secondary',
};

export type ToolbarDropdown = ToolbarBase & {
    options: Array<ToolbarDropdownOptionConfig>,
};

export type ToolbarButton = ToolbarBase & {
    onClick: (columnIndex?: string | number) => void,
};

export type ToolbarDropdownConfig = ToolbarDropdown & { type: 'dropdown' };

export type ToolbarButtonConfig = ToolbarButton & { type: 'button'};

export type ToolbarItemConfig = ToolbarDropdownConfig | ToolbarButtonConfig;
