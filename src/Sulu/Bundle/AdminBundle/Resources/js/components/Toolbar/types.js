// @flow
import type {Element, Node} from 'react';
import ItemsComponent from './Items';
import IconsComponent from './Icons';
import ButtonComponent from './Button';
import DropdownComponent from './Dropdown';
import SelectComponent from './Select';

export type Item =
    Element<typeof ButtonComponent>
    | Element<typeof DropdownComponent>
    | Element<typeof SelectComponent>;
export type Group = Element<typeof ItemsComponent> | Element<typeof IconsComponent>;

export type Skin = 'light' | 'dark';

export type Button = {
    active?: boolean,
    children?: Node,
    disabled?: boolean,
    hasOptions?: boolean,
    icon?: string,
    loading?: boolean,
    onClick: () => ?Promise<*>,
    primary?: boolean,
    size?: string,
    skin?: Skin,
    value?: string | number,
};

export type DropdownOption = {
    disabled?: boolean,
    label: string | number,
    onClick?: () => void,
    skin?: Skin,
};

export type SelectOption = {
    disabled?: boolean,
    label: string | number,
    skin?: Skin,
    value: string | number,
};

export type Dropdown = {
    disabled?: boolean,
    icon?: string,
    label?: string | number,
    loading?: boolean,
    options: Array<DropdownOption>,
    size?: string,
    skin?: Skin,
};

export type Select = {
    className?: string,
    disabled?: boolean,
    icon?: string,
    label?: string | number,
    loading?: boolean,
    onChange: (optionValue: string | number) => void,
    options: Array<SelectOption>,
    size?: string,
    skin?: Skin,
    value: string | number,
};
