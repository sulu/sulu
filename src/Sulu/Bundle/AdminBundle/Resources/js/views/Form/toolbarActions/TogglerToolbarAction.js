// @flow
import {action, computed, observable} from 'mobx';
import ResourceRequester from '../../../services/ResourceRequester';
import AbstractFormToolbarAction from './AbstractFormToolbarAction';

export default class TogglerToolbarAction extends AbstractFormToolbarAction {
    @observable loading: boolean = false;

    @computed get property() {
        const {
            property,
        } = this.options;

        if (typeof property !== 'string') {
            throw new Error('The "property" option must be a string value!');
        }

        return property;
    }

    @computed get label() {
        const {
            label,
        } = this.options;

        if (typeof label !== 'string') {
            throw new Error('The "label" option must be a string value!');
        }

        return label;
    }

    @computed get activateAction() {
        const {
            activate,
        } = this.options;

        if (typeof activate !== 'string') {
            throw new Error('The "activate" option must be a string value!');
        }

        return activate;
    }

    @computed get deactivateAction() {
        const {
            deactivate,
        } = this.options;

        if (typeof deactivate !== 'string') {
            throw new Error('The "deactivate" option must be a string value!');
        }

        return deactivate;
    }

    @computed get isActive() {
        return this.resourceFormStore.data[this.property];
    }

    getToolbarItemConfig() {
        if (this.resourceFormStore.loading || !this.resourceFormStore.data.id) {
            return null;
        }

        return {
            type: 'toggler',
            onClick: this.handleTogglerClick,
            label: this.label,
            loading: this.loading,
            value: this.isActive,
        };
    }

    @action handleTogglerClick = () => {
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
                action: this.isActive ? this.deactivateAction : this.activateAction,
                locale,
                id,
            }
        ).then(action((response) => {
            this.resourceFormStore.set(this.property, response[this.property]);
            this.loading = false;
            this.form.showSuccessSnackbar();
        })).catch(action((error) => {
            this.form.errors.push(error);
            this.loading = false;
        }));
    };
}
