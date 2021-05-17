// @flow
import ItemsComponent from './Items';
import IconsComponent from './Icons';
import ButtonComponent from './Button';
import DropdownComponent from './Dropdown';
import PopoverComponent from './Popover';
import SelectComponent from './Select';
import TogglerComponent from './Toggler';
import type {Element, Node, ElementRef} from 'react';

export type Item =
    Element<typeof ButtonComponent>
    | Element<typeof DropdownComponent>
    | Element<typeof PopoverComponent>
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

export type Popover = {|
    children: (onClose: () => void) => Node,
    className?: string,
    disabled?: boolean,
    icon?: string,
    label?: string | number,
    loading?: boolean,
    showText?: boolean,
    size?: string,
    skin?: Skin,
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

export type SelectOption<T: ?string | number> = {|
    disabled?: boolean,
    label: string | number,
    skin?: Skin,
    value: T,
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

export type Select<T> = {|
    className?: string,
    disabled?: boolean,
    icon?: string,
    label?: string | number,
    loading?: boolean,
    onChange: (optionValue: T) => void,
    options: Array<SelectOption<T>>,
    showText?: boolean,
    size?: string,
    skin?: Skin,
    value: T,
|};
