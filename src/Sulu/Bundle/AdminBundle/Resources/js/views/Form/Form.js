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
        this.formStore.destroy();
        const {resourceStore, router} = this.props;

        if (resourceStore.locale) {
            router.unbind('locale', resourceStore.locale);
        }
    }

    handleSubmit = () => {
        const {resourceStore, router} = this.props;

        const {
            route: {
                options: {
                    editRoute,
                },
            },
        } = router;

        if (editRoute) {
            resourceStore.destroy();
        }

        this.formStore.save()
            .then(() => {
                if (editRoute) {
                    router.navigate(editRoute, {id: resourceStore.id, locale: resourceStore.locale});
                }
            })
            .catch(() => {
                // TODO show an error label
            });
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
    const formTypes = this.formStore.types;

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
            icon: 'su-save',
            disabled: !this.props.resourceStore.dirty,
            loading: this.props.resourceStore.saving,
            onClick: () => {
                this.form.submit();
            },
        },
    ];

    if (this.formStore.typesLoading || Object.keys(formTypes).length > 0) {
        items.push({
            type: 'select',
            icon: 'fa-paint-brush',
            onChange: (value) => {
                this.formStore.changeType(value);
            },
            loading: this.formStore.typesLoading,
            value: this.formStore.type,
            options: Object.keys(formTypes).map((key) => ({
                value: formTypes[key].key,
                label: formTypes[key].title,
            })),
        });
    }

    return {
        backButton,
        locale,
        items,
    };
});
