// @flow
import jexl from 'jexl';
import type {ToolbarItemConfig} from '../../../containers/Toolbar/types';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class TypeToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig(): ToolbarItemConfig {
        const formTypes = this.resourceFormStore.types;

        if (!this.resourceFormStore.typesLoading && Object.keys(formTypes).length === 0) {
            throw new Error('The ToolbarAction for types only works with entities actually supporting types!');
        }

        const {
            visible_condition: visibleCondition,
            disable_condition: disableCondition,
        } = this.options;

        if (visibleCondition && !jexl.evalSync(visibleCondition, this.resourceFormStore.data)) {
            return;
        }

        let isDisabled = false;
        if (disableCondition && jexl.evalSync(disableCondition, this.resourceFormStore.data)) {
            isDisabled = true;
        }

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
            options: Object.keys(formTypes).map((key: string) => ({
                value: formTypes[key].key,
                label: formTypes[key].title,
            })),
        };
    }
}
