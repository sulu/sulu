// @flow
import {action, computed, observable} from 'mobx';
import {AbstractFormToolbarAction} from 'sulu-admin-bundle/views';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';

export default class LockUserToolbarAction extends AbstractFormToolbarAction {
    @observable loading: boolean = false;

    @computed get userIsLocked() {
        return this.resourceFormStore.data.locked;
    }

    getToolbarItemConfig() {
        if (!this.resourceFormStore.data.enabled) {
            return null;
        }

        return {
            type: 'toggler',
            onClick: this.handleLockUserTogglerClick,
            disabled: this.resourceFormStore.loading || !this.resourceFormStore.data.id,
            label: translate(this.userIsLocked ? 'sulu_security.user_locked' : 'sulu_security.lock_user'),
            loading: this.loading,
            value: this.userIsLocked,
        };
    }

    @action handleLockUserTogglerClick = () => {
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
                action: this.userIsLocked ? 'unlock' : 'lock',
                locale,
                id,
            }
        ).then(action((response) => {
            this.resourceFormStore.set('locked', response.locked);
            this.loading = false;
            this.form.showSuccessSnackbar();
        })).catch(action((error) => {
            this.form.errors.push(error);
            this.loading = false;
        }));
    };
}
