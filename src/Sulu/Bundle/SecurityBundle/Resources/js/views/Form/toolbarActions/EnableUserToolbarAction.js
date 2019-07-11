// @flow
import {action, observable} from 'mobx';
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import type {ToolbarItemConfig} from 'sulu-admin-bundle/types';
import {translate} from "sulu-admin-bundle/utils/Translator";

export default class EnableUserToolbarAction extends AbstractFormToolbarAction {
    @observable loading: boolean = false;

    getToolbarItemConfig(): ToolbarItemConfig {
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
            id,
            locale,
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
            this.resourceFormStore.setMultiple(response);
            this.resourceFormStore.dirty = false;
            this.loading = false;
            this.form.showSuccessSnackbar();
        })).catch(action((error) => {
            this.form.errors.push(error);
            this.loading = false;
        }));
    }
}
