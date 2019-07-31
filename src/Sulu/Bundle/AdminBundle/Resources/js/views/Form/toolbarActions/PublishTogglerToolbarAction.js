// @flow
import {action, computed, observable} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import {translate} from '../../../utils/Translator';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class PublishTogglerToolbarAction extends AbstractFormToolbarAction {
    @observable loading: boolean = false;

    @computed get isPublished() {
        return this.resourceFormStore.data.published;
    }

    getToolbarItemConfig() {
        if (this.resourceFormStore.loading || !this.resourceFormStore.data.id) {
            return null;
        }

        return {
            type: 'toggler',
            onClick: this.handlePublishTogglerClick,
            label: translate('sulu_admin.publish'),
            loading: this.loading,
            value: this.isPublished,
        };
    }

    @action handlePublishTogglerClick = () => {
        const {
            resourceKey,
            locale,
            data: {
                id,
            },
        } = this.resourceFormStore;

        this.loading = true;
        ResourceRequester.post(
            resourceKey,
            undefined,
            {
                action: this.isPublished ? 'unpublish' : 'publish',
                locale,
                id,
            }
        ).then(action((response) => {
            this.resourceFormStore.set('published', response.published);
            this.loading = false;
            this.form.showSuccessSnackbar();
        })).catch(action((error) => {
            this.form.errors.push(error);
            this.loading = false;
        }));
    };
}
