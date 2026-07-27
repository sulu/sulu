// @flow
import {action, observable} from 'mobx';
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';

export default class ResetTwoFactorToolbarAction extends AbstractFormToolbarAction {
    @observable loading: boolean = false;

    getToolbarItemConfig() {
        const {data} = this.resourceFormStore;

        if (this.resourceFormStore.loading || !data.id || !data.twoFactor || !data.twoFactor.method) {
            return null;
        }

        return {
            type: 'button',
            icon: 'su-lock',
            onClick: this.handleResetTwoFactorButtonClick,
            label: translate('sulu_security.reset_two_factor'),
            loading: this.loading,
        };
    }

    @action handleResetTwoFactorButtonClick = () => {
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
                action: 'reset-two-factor',
                locale,
                id,
            }
        ).then(action((response) => {
            this.resourceFormStore.change('twoFactor', response.twoFactor, {isServerValue: true});
            this.loading = false;
            this.form.showSuccessSnackbar();
        })).catch(action((error) => {
            this.form.errors.push(error);
            this.loading = false;
        }));
    };
}
