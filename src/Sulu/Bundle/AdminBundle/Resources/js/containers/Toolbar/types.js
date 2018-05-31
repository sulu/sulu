// @flow
import type {Button, Dropdown, Select} from '../../components/Toolbar/types';

export type {Button, Dropdown, Select};

export type ButtonItem = Button & { type: 'button' };

export type DropdownItem = Dropdown & { type: 'dropdown' };

export type SelectItem = Select & { type: 'select' };

export type ToolbarItem = ButtonItem | DropdownItem | SelectItem;

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
    items?: Array<ToolbarItem>,
    locale?: Select,
};
