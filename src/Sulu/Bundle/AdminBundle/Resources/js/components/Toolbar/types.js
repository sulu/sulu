// @flow
import type {Node} from 'react';

export type Button = {
    children?: Node,
    onClick: () => ?Promise<*>,
    value?: string | number,
    icon?: string,
    size?: string,
    disabled?: boolean,
    active?: boolean,
    hasOptions?: boolean,
    loading?: boolean,
    skin?: 'primary',
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
    loading?: boolean,
};

export type Select = {
    value: string | number,
    options: Array<SelectOption>,
    onChange: (optionValue: string | number) => void,
    label?: string | number,
    icon?: string,
    size?: string,
    disabled?: boolean,
    loading?: boolean,
};
