// @flow
import React from 'react';
import {action, computed, observable, isObservableArray} from 'mobx';
import {observer} from 'mobx-react';
import {default as FormContainer, FormStore} from '../../containers/Form';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import ResourceStore from '../../stores/ResourceStore';
import toolbarActionRegistry from './registries/ToolbarActionRegistry';
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
    @observable toolbarActions = [];

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

        const formStoreOptions = routerAttributesToFormStore
            ? routerAttributesToFormStore.reduce(
                (options: Object, routerAttribute: string) => {
                    options[routerAttribute] = attributes[routerAttribute];
                    return options;
                },
                {}
            )
            : {};

        if (this.hasOwnResourceStore) {
            let locale = resourceStore.locale;
            if (!locale && this.locales) {
                locale = observable.box();
            }

            this.resourceStore = idQueryParameter
                ? new ResourceStore(resourceKey, id, {locale}, formStoreOptions, idQueryParameter)
                : new ResourceStore(resourceKey, id, {locale}, formStoreOptions);
        } else {
            this.resourceStore = resourceStore;
        }

        this.formStore = new FormStore(this.resourceStore, formStoreOptions);

        if (this.resourceStore.locale) {
            router.bind('locale', this.resourceStore.locale);
        }
    }

    @action componentDidMount() {
        const form = this.form;
        if (!form) {
            throw new Error('The form ref has not been set! This should not happen and is likely a bug.');
        }

        const {router} = this.props;
        const {
            route: {
                options: {
                    toolbarActions,
                },
            },
        } = router;

        if (!Array.isArray(toolbarActions) && !isObservableArray(toolbarActions)) {
            throw new Error('The view "Form" needs some defined toolbarActions to work properly!');
        }

        this.toolbarActions = toolbarActions.map((toolbarAction) => new (toolbarActionRegistry.get(toolbarAction))(
            this.formStore,
            form,
            router
        ));
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

    handleSubmit = (actionParameter) => {
        const {resourceStore, router} = this.props;

        const {
            attributes,
            route: {
                options: {
                    editRoute,
                    routerAttributesToEditRoute,
                },
            },
        } = router;

        if (editRoute) {
            resourceStore.destroy();
        }

        const saveOptions = {
            action: actionParameter,
        };

        const editRouteParameters = routerAttributesToEditRoute
            ? routerAttributesToEditRoute.reduce(
                (parameters: Object, routerAttribute: string) => {
                    parameters[routerAttribute] = attributes[routerAttribute];
                    return parameters;
                },
                {}
            )
            : {};

        return this.formStore.save(saveOptions)
            .then((response) => {
                this.showSuccessSnackbar();

                if (editRoute) {
                    router.navigate(
                        editRoute,
                        {id: resourceStore.id, locale: resourceStore.locale, ...editRouteParameters}
                    );
                }

                if (this.hasOwnResourceStore) {
                    // Reload parent ResourceStore, since its data might have changed due to changes in this Form
                    resourceStore.load();
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
                {this.toolbarActions.map((toolbarAction) => toolbarAction.getNode())}
            </div>
        );
    }
}

export default withToolbar(Form, function() {
    const {router} = this.props;
    const {backRoute} = router.route.options;
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

    const items = this.toolbarActions
        .map((toolbarAction) => toolbarAction.getToolbarItemConfig())
        .filter((item) => item !== undefined);

    return {
        backButton,
        errors,
        locale,
        items,
        showSuccess,
    };
});
