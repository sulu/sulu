// @flow

export type BackButtonConfig = {
    onClick: () => void,
};

export type ButtonConfig = {|
    value: string,
    onClick: () => void,
    icon?: string,
    size?: string,
    disabled?: boolean,
    isActive?: boolean,
    hasOptions?: boolean,
|};

export type DropdownOptionConfig = {
    label: string,
    onClick?: () => void,
    disabled?: boolean,
};

export type DropdownConfig = {|
    options: Array<DropdownOptionConfig>,
    label?: string,
    icon?: string,
    size?: string,
    disabled?: boolean,
|};

// this is the type of the config object,
// not the props type of the DropdownOption component
export type SelectOptionConfig = {
    value: string,
    label: string,
    disabled?: boolean,
};

export type SelectConfig = {|
    value: string,
    onChange: (optionValue: string) => void,
    options: Array<SelectOptionConfig>,
    label?: string,
    icon?: string,
    size?: string,
    disabled?: boolean,
|};

export const ToolbarItemTypes = {
    Button: 'button',
    Dropdown: 'dropdown',
    Select: 'select',
};

export type ToolbarItem = ToolbarItemTypes.Button | ToolbarItemTypes.Dropdown | ToolbarItemTypes.Select;

export type ToolbarConfig = {
    icons?: Array<string>,
    locale?: ?DropdownConfig,
    buttons?: Array<ButtonConfig | DropdownConfig | SelectConfig>,
    backButton?: ?BackButtonConfig,
};
