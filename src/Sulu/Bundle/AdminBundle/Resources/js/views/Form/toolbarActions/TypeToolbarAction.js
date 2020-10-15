// @flow
import jexl from 'jexl';
import type {ToolbarItemConfig} from '../../../containers/Toolbar/types';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class TypeToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig(): ?ToolbarItemConfig<string> {
        const formTypes = Object.keys(this.resourceFormStore.types).map((key) => this.resourceFormStore.types[key]);

        if (!this.resourceFormStore.typesLoading && formTypes.length === 0) {
            throw new Error('The ToolbarAction for types only works with entities actually supporting types!');
        }

        const {
            disabled_condition: disabledCondition,
            sort_by: sortBy,
        } = this.options;

        if (sortBy !== undefined && typeof sortBy !== 'string') {
            throw new Error('The "sort_by" option must be a string if given!');
        }

        const isDisabled = disabledCondition ? jexl.evalSync(disabledCondition, this.resourceFormStore.data) : false;

        const sortedTypes = sortBy
            ? formTypes.sort((t1, t2) => String(t1[sortBy]).localeCompare(String(t2[sortBy])))
            : formTypes;

        return {
            type: 'select',
            icon: 'su-brush',
            onChange: (value: string | number) => {
                if (typeof value !== 'string') {
                    throw new Error('Only strings are valid as a form type!');
                }

                this.resourceFormStore.changeType(value);
            },
            loading: this.resourceFormStore.typesLoading,
            value: this.resourceFormStore.type,
            disabled: isDisabled,
            options: sortedTypes.map((type) => ({
                value: type.key,
                label: type.title,
            })),
        };
    }
}
