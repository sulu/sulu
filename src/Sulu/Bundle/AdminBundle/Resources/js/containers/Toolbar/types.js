// @flow

export type BackButtonType = {
    onClick: () => void,
};

export type DefaultButtonType = {|
    value: string,
    onClick: () => void,
    icon?: string,
    size?: string,
    disabled?: boolean,
    isActive?: boolean,
    hasOptions?: boolean,
|};

// this is the type of the config object,
// not the props type of the DropdownOption component
export type OptionConfigType = {
    value: string,
    label: string,
    selected?: boolean,
    disabled?: boolean,
};

export type DropdownButtonType = {|
    value: string,
    onChange: (optionValue: string) => void,
    options: Array<OptionConfigType>,
    label?: string,
    icon?: string,
    size?: string,
    isOpen?: boolean,
    disabled?: boolean,
    setValueOnChange?: boolean,
|};

export type ToolbarConfig = {
    icons?: Array<string>,
    locale?: ?DropdownButtonType,
    buttons?: Array<DefaultButtonType | DropdownButtonType>,
    backButton?: ?BackButtonType,
};
