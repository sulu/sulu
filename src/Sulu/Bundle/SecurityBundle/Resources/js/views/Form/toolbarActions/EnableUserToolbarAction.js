// @flow
import {action, observable} from 'mobx';
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';

export default class EnableUserToolbarAction extends AbstractFormToolbarAction {
    @observable loading: boolean = false;

    getToolbarItemConfig() {
        if (this.resourceFormStore.loading || !this.resourceFormStore.data.id || this.resourceFormStore.data.enabled) {
            return null;
        }

        return {
            type: 'button',
            icon: 'su-enter',
            onClick: this.handleEnableUserButtonClick,
            label: translate('sulu_security.enable_user'),
            loading: this.loading,
        };
    }

    @action handleEnableUserButtonClick = () => {
        const {
            locale,
            data: {
                id,
            },
        } = this.resourceFormStore;

        this.loading = true;
        ResourceRequester.post(
            'users',
            undefined,
            {
                action: 'enable',
                locale,
                id,
            }
        ).then(action((response) => {
            this.resourceFormStore.set('enabled', response.enabled);
            this.loading = false;
            this.form.showSuccessSnackbar();
        })).catch(action((error) => {
            this.form.errors.push(error);
            this.loading = false;
        }));
    };
}
