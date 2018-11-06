// @flow
import type {ChildrenArray, Element} from 'react';
import Select from './Select';
import Action from './Action';
import Option from './Option';

export type SelectProps = {
    children: SelectChildren,
    disabled?: boolean,
    icon?: string,
    skin: Skin,
}

export type Skin = 'default' | 'flat' | 'dark';

export type OptionSelectedVisualization = 'icon' | 'checkbox';

export type SelectChild = Element<typeof Option> | Element<typeof Select.Divider> | Element<typeof Action>;
export type SelectChildren = ChildrenArray<SelectChild>;
