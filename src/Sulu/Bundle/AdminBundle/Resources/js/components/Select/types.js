// @flow
import type {ChildrenArray, Element} from 'react';
import Select from './Select';
import Action from './Action';
import Option from './Option';

export type SelectProps<T> = {|
    children: SelectChildren<T>,
    disabled: boolean,
    icon?: string,
    skin: Skin,
|}

export type Skin = 'default' | 'flat' | 'dark';

export type OptionSelectedVisualization = 'icon' | 'checkbox';

export type SelectChild<T> = Element<Class<Option<T>>> | Element<Class<Select.Divider>> | Element<Class<Action>>;
export type SelectChildren<T> = ChildrenArray<SelectChild<T>>;
