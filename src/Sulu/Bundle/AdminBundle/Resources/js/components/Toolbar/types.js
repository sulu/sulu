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

export type Skins = 'light' | 'dark';

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
    asdf?: 'primary',
    skin?: Skins,
};

export type DropdownOption = {
    label: string | number,
    onClick?: () => void,
    disabled?: boolean,
    skin?: Skins,
};

export type SelectOption = {
    label: string | number,
    value: string | number,
    disabled?: boolean,
    skin?: Skins,
};

export type Dropdown = {
    options: Array<DropdownOption>,
    label?: string | number,
    icon?: string,
    size?: string,
    disabled?: boolean,
    loading?: boolean,
    skin?: Skins,
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
    skin?: Skins,
};
