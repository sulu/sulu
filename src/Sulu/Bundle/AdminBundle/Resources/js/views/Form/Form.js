// @flow
import React from 'react';
import {default as FormContainer, FormStore} from '../../containers/Form';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../utils/Translator';
import ResourceStore from '../../stores/ResourceStore';
import formStyles from './form.scss';

type Props = ViewProps & {
    resourceStore: ResourceStore,
};

class Form extends React.PureComponent<Props> {
    formStore: FormStore;
    form: ?FormContainer;

    componentWillMount() {
        const {resourceStore, router} = this.props;
        this.formStore = new FormStore(resourceStore);

        if (resourceStore.locale) {
            router.bind('locale', resourceStore.locale);
        }
    }

    componentWillUnmount() {
        const {resourceStore, router} = this.props;

        if (resourceStore.locale) {
            router.unbind('locale', resourceStore.locale);
        }
    }

    handleSubmit = () => {
        this.formStore.save();
    };

    setFormRef = (form) => {
        this.form = form;
    };

    render() {
        return (
            <div className={formStyles.form}>
                <FormContainer
                    ref={this.setFormRef}
                    store={this.formStore}
                    onSubmit={this.handleSubmit}
                />
            </div>
        );
    }
}

export default withToolbar(Form, function() {
    const {router} = this.props;
    const {backRoute, locales} = router.route.options;
    const formTypes = this.formStore.types.map((type) => ({
        value: type.key,
        label: type.title,
    }));

    const backButton = backRoute
        ? {
            onClick: () => {
                const {resourceStore} = this.props;

                const options = {};
                if (resourceStore.locale) {
                    options.locale = resourceStore.locale.get();
                }
                router.restore(backRoute, options);
            },
        }
        : undefined;
    const locale = locales
        ? {
            value: this.props.resourceStore.locale.get(),
            onChange: (locale) => {
                this.props.resourceStore.setLocale(locale);
            },
            options: locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    const items = [
        {
            type: 'button',
            value: translate('sulu_admin.save'),
            icon: 'floppy-o',
            disabled: !this.props.resourceStore.dirty,
            loading: this.props.resourceStore.saving,
            onClick: () => {
                this.form.submit();
            },
        },
    ];

    if (this.formStore.typesLoading || formTypes.length > 0) {
        items.push({
            type: 'select',
            icon: 'paint-brush',
            onChange: () => {},
            loading: this.formStore.typesLoading,
            value: 'sidebar',
            options: formTypes,
        });
    }

    return {
        backButton,
        locale,
        items,
    };
});
