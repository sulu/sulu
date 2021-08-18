// @flow
import jexl from 'jexl';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class SaveToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        const {
            label = 'sulu_admin.save',
            visible_condition: visibleCondition,
            options: submitOptions,
        } = this.options;

        const {data, dirty, saving} = this.resourceFormStore;

        if (typeof label !== 'string') {
            throw new Error('The "label" option must be a string!');
        }

        if (submitOptions === null || typeof submitOptions !== 'object') {
            throw new Error('The "options" option must be an object!');
        }

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, data);

        if (visibleConditionFulfilled) {
            return {
                disabled: !dirty,
                icon: 'su-save',
                label: translate(label),
                loading: saving,
                onClick: () => {
                    this.form.submit(submitOptions);
                },
                type: 'button',
            };
        }
    }
}
