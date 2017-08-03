// @flow
export type DefaultButtonType = {|
    value: string,
    onClick: () => void,
    icon?: string,
    disabled?: boolean,
    hasOptions?: boolean,
    isActive?: boolean,
|};

type OptionType = {
    value: string,
};

export type DropdownButtonType = {|
    value: string,
    onChange: (selectedOption: OptionType) => void,
    options: Array<OptionType>,
    isOpen?: boolean,
    setValueOnSelect?: boolean,
    defaultValue?: string,
    icon?: string,
    disabled?: boolean,
|};

export type ToolbarConfig = {
    buttons: Array<DefaultButtonType | DropdownButtonType>,
};
