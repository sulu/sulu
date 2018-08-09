// @flow
import type {Node} from 'react';
import type {IObservableValue} from 'mobx';
import type {Button, Dropdown, Select} from '../../components/Toolbar/types';

export type {Button, Dropdown, Select};
export type ButtonItemConfig = Button & { type: 'button' };
export type DropdownItemConfig = Dropdown & { type: 'dropdown' };
export type SelectItemConfig = Select & { type: 'select' };
export type ToolbarItemConfig = ButtonItemConfig | DropdownItemConfig | SelectItemConfig;

export type ToolbarProps = {
    storeKey?: string,
    onNavigationButtonClick?: () => void,
    navigationOpen?: boolean,
};

type Error = {
    code: number,
    message: string,
};

export type ToolbarConfig = {
    backButton?: Button,
    disableAll?: boolean,
    errors?: Array<Error>,
    icons?: Array<string>,
    items?: Array<ToolbarItemConfig>,
    locale?: Select,
    showSuccess?: IObservableValue<boolean>,
};

export interface ToolbarAction {
    getNode(): Node,
    getToolbarItemConfig(): ToolbarItemConfig,
}
