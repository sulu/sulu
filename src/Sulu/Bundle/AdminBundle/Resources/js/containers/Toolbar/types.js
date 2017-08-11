// @flow

export type Button = {
    onClick: () => void,
    value?: string | number,
    icon?: string,
    size?: string,
    disabled?: boolean,
    isActive?: boolean,
    hasOptions?: boolean,
};

export type DropdownOption = {
    label: string | number,
    onClick?: () => void,
    disabled?: boolean,
};

export type SelectOption = {
    label: string | number,
    value: string | number,
    disabled?: boolean,
};

export type Dropdown = {
    options: Array<DropdownOption>,
    label?: string | number,
    icon?: string,
    size?: string,
    disabled?: boolean,
};

export type Select = {
    value: string | number,
    options: Array<SelectOption>,
    onChange: (optionValue: string | number) => void,
    label?: string | number,
    icon?: string,
    size?: string,
    disabled?: boolean,
};

export type ButtonItem =
    & Button
    & { type: 'button' };

export type DropdownItem =
    & Dropdown
    & { type: 'dropdown' };

export type SelectItem =
    & Select
    & { type: 'select' };

export type ToolbarProps = {
    storeKey?: string,
};

export type ToolbarConfig = {
    icons?: Array<string>,
    items?: Array<ButtonItem | DropdownItem | SelectItem>,
    locale?: Select,
    backButton?: Button,
};
