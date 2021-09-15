// @flow
import jexl from 'jexl';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class PublishToolbarAction extends AbstractFormToolbarAction {
    getToolbarItemConfig() {
        const {
            visible_condition: visibleCondition,
        } = this.options;

        const {dirty, data} = this.resourceFormStore;

        const visibleConditionFulfilled = !visibleCondition || jexl.evalSync(visibleCondition, this.conditionData);

        if (visibleConditionFulfilled) {
            return {
                label: translate('sulu_admin.publish'),
                disabled: dirty || data.publishedState === undefined || !!data.publishedState,
                onClick: () => {
                    this.form.submit({action: 'publish'});
                },
                type: 'button',
            };
        }
    }
}
