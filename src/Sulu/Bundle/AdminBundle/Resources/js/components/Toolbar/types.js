// @flow
import type {Element, Node, ElementRef} from 'react';
import ItemsComponent from './Items';
import IconsComponent from './Icons';
import ButtonComponent from './Button';
import DropdownComponent from './Dropdown';
import SelectComponent from './Select';
import TogglerComponent from './Toggler';

export type Item =
    Element<typeof ButtonComponent>
    | Element<typeof DropdownComponent>
    | Element<typeof SelectComponent>
    | Element<typeof TogglerComponent>;
export type Group = Element<typeof ItemsComponent> | Element<typeof IconsComponent>;

export type Skin = 'light' | 'dark';

export type Button = {|
    active?: boolean,
    buttonRef?: (ref: ElementRef<'button'>) => void,
    children?: Node,
    disabled?: boolean,
    hasOptions?: boolean,
    icon?: string,
    label?: string | number,
    loading?: boolean,
    onClick: () => ?Promise<*>,
    primary?: boolean,
    showText?: boolean,
    size?: string,
    skin?: Skin,
    success?: boolean,
|};

export type Toggler = {|
    disabled?: boolean,
    label: string,
    loading?: boolean,
    onClick: () => void,
    skin?: Skin,
    value: boolean,
|};

export type DropdownOption = {|
    disabled?: boolean,
    label: string | number,
    onClick?: () => mixed,
    skin?: Skin,
|};

export type SelectOption = {|
    disabled?: boolean,
    label: string | number,
    skin?: Skin,
    value: string | number,
|};

export type Dropdown = {|
    disabled?: boolean,
    icon?: string,
    label?: string | number,
    loading?: boolean,
    options: Array<DropdownOption>,
    showText?: boolean,
    size?: string,
    skin?: Skin,
|};

export type Select = {|
    className?: string,
    disabled?: boolean,
    icon?: string,
    label?: string | number,
    loading?: boolean,
    onChange: (optionValue: string | number) => void,
    options: Array<SelectOption>,
    showText?: boolean,
    size?: string,
    skin?: Skin,
    value: string | number,
|};
