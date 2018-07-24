// @flow
import React from 'react';
import {action, computed, isObservableArray, observable} from 'mobx';
import {observer} from 'mobx-react';
import {default as FormContainer, FormStore} from '../../containers/Form';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../utils/Translator';
import ResourceStore from '../../stores/ResourceStore';
import formStyles from './form.scss';

type Props = ViewProps & {
    locales: Array<string>,
    resourceStore: ResourceStore,
};

@observer
class Form extends React.Component<Props> {
    resourceStore: ResourceStore;
    formStore: FormStore;
    form: ?FormContainer;
    @observable errors = [];
    showSuccess = observable.box(false);

    @computed get hasOwnResourceStore() {
        const {
            resourceStore,
            route: {
                options: {
                    resourceKey,
                },
            },
        } = this.props;

        return resourceKey && resourceStore.resourceKey !== resourceKey;
    }

    @computed get locales() {
        const {
            locales: propsLocales,
            route: {
                options: {
                    locales: routeLocales,
                },
            },
        } = this.props;

        return routeLocales ? routeLocales : propsLocales;
    }

    constructor(props: Props) {
        super(props);

        const {resourceStore, router} = this.props;
        const {
            attributes,
            route: {
                options: {
                    idQueryParameter,
                    resourceKey,
                    routerAttributesToFormStore,
                },
            },
        } = router;
        const {id} = attributes;

        if (!resourceStore) {
            throw new Error(
                'The view "Form" needs a resourceStore to work properly.'
                + 'Did you maybe forget to make this view a child of a "ResourceTabs" view?'
            );
        }

        if (this.hasOwnResourceStore) {
            let locale = resourceStore.locale;
            if ((Array.isArray(this.locales) || isObservableArray(this.locales)) && this.locales.length > 0) {
                const parentLocale = resourceStore.locale ? resourceStore.locale.get() : undefined;
                if (this.locales.includes(parentLocale)) {
                    locale = observable.box(parentLocale);
                } else {
                    locale = observable.box();
                }
            }

            this.resourceStore = idQueryParameter
                ? new ResourceStore(resourceKey, id, {locale: locale}, {}, idQueryParameter)
                : new ResourceStore(resourceKey, id, {locale: locale});
        } else {
            this.resourceStore = resourceStore;
        }

        const formStoreOptions = routerAttributesToFormStore
            ? routerAttributesToFormStore.reduce(
                (options: Object, routerAttribute: string) => {
                    options[routerAttribute] = attributes[routerAttribute];
                    return options;
                },
                {}
            )
            : {};
        this.formStore = new FormStore(this.resourceStore, formStoreOptions);

        if (this.resourceStore.locale) {
            router.bind('locale', this.resourceStore.locale);
        }
    }

    componentWillUnmount() {
        this.formStore.destroy();

        if (this.hasOwnResourceStore) {
            this.resourceStore.destroy();
        }
    }

    @action showSuccessSnackbar = () => {
        this.showSuccess.set(true);
    };

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

        return this.formStore.save()
            .then((response) => {
                this.showSuccessSnackbar();

                if (editRoute) {
                    router.navigate(editRoute, {id: resourceStore.id, locale: resourceStore.locale});
                }

                return response;
            })
            .catch((errorResponse) => {
                return errorResponse.json().then(action((error) => {
                    this.errors.push(error);
                }));
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
    const {backRoute} = router.route.options;
    const formTypes = this.formStore.types;
    const {errors, resourceStore, showSuccess} = this;

    const backButton = backRoute
        ? {
            onClick: () => {
                const options = {};
                if (resourceStore.locale) {
                    options.locale = resourceStore.locale.get();
                }
                router.restore(backRoute, options);
            },
        }
        : undefined;
    const locale = this.locales
        ? {
            value: resourceStore.locale.get(),
            onChange: (locale) => {
                resourceStore.setLocale(locale);
            },
            options: this.locales.map((locale) => ({
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
            disabled: !resourceStore.dirty,
            loading: resourceStore.saving,
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
        errors,
        locale,
        items,
        showSuccess,
    };
});
