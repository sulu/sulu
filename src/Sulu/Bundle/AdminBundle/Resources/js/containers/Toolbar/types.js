// @flow

export type ToolbarItem = 'button' | 'dropdown' | 'select';

export type ButtonConfig = {|
    type: ToolbarItem,
    onClick: () => void,
    value?: string,
    icon?: string,
    size?: string,
    disabled?: boolean,
    isActive?: boolean,
    hasOptions?: boolean,
|};

export type BackButtonConfig = {|
    onClick: () => void,
    value?: string,
    icon?: string,
    disabled?: boolean,
|};

export type OptionProps = {|
    value: string | number,
    label: string | number,
    onClick: (value?: string | number) => void,
    size?: string,
    selected?: boolean,
    disabled?: boolean,
|};

export type DropdownOptionConfig = {|
    label: string | number,
    onClick?: () => void,
    disabled?: boolean,
|};

export type DropdownConfig = {|
    type: ToolbarItem,
    options: Array<DropdownOptionConfig>,
    label?: string,
    size?: string,
    disabled?: boolean,
|};

export type SelectOptionConfig = {|
    value: string | number,
    label: string | number,
    disabled?: boolean,
|};

export type SelectConfig = {|
    type: ToolbarItem,
    onChange: (optionValue: string | number) => void,
    options: Array<SelectOptionConfig>,
    value?: string,
    label?: string,
    icon?: string,
    size?: string,
    disabled?: boolean,
|};

export type LocaleConfig = {|
    onChange: (optionValue: string | number) => void,
    options: Array<SelectOptionConfig>,
    value?: string,
    label?: string,
    icon?: string,
    size?: string,
    disabled?: boolean,
|};

export type ItemConfig = ButtonConfig | DropdownConfig | SelectConfig;

export type ToolbarConfig = {
    icons?: Array<string>,
    locale?: ?LocaleConfig,
    items?: ?Array<ItemConfig>,
    backButton?: ?BackButtonConfig,
};
