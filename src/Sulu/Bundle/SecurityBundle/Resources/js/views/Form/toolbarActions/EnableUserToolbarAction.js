// @flow
import {action, observable} from 'mobx';
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import type {ButtonItemConfig} from 'sulu-admin-bundle/containers/Toolbar/types';

export default class EnableUserToolbarAction extends AbstractFormToolbarAction {
    @observable loading: boolean = false;

    getToolbarItemConfig(): ButtonItemConfig {
        const userIsEnabled = this.resourceFormStore.data.enabled;

        return {
            type: 'button',
            icon: 'su-check-circle',
            onClick: this.handleEnableUserButtonClick,
            disabled: this.resourceFormStore.loading || !this.resourceFormStore.data.id || userIsEnabled,
            label: translate(userIsEnabled ? 'sulu_security.user_enabled' : 'sulu_security.enable_user'),
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
