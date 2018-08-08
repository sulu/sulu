// @flow
import AbstractToolbarAction from '../toolbarActions/AbstractToolbarAction';
import type {ToolbarItem} from '../../../containers/Toolbar/types';

export default class TypeToolbarAction extends AbstractToolbarAction {
    getToolbarItemConfig(): ToolbarItem {
        const formTypes = this.formStore.types;

        if (!this.formStore.typesLoading && Object.keys(formTypes).length === 0) {
            throw new Error('The ToolbarAction for types only works with entities actually supporting types!');
        }

        return {
            type: 'select',
            icon: 'fa-paint-brush',
            onChange: (value: string | number) => {
                if (typeof value !== 'string') {
                    throw new Error('Only strings are valid as a form type!');
                }

                this.formStore.changeType(value);
            },
            loading: this.formStore.typesLoading,
            value: this.formStore.type,
            options: Object.keys(formTypes).map((key: string) => ({
                value: formTypes[key].key,
                label: formTypes[key].title,
            })),
        };
    }
}
