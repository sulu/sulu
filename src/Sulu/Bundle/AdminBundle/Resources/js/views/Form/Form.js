// @flow
import type {ElementRef} from 'react';
import React from 'react';
import type {IObservableValue} from 'mobx';
import {action, computed, toJS, isObservableArray, observable, when} from 'mobx';
import {observer} from 'mobx-react';
import equals from 'fast-deep-equal';
import jexl from 'jexl';
import Dialog from '../../components/Dialog';
import PublishIndicator from '../../components/PublishIndicator';
import {default as FormContainer, ResourceFormStore} from '../../containers/Form';
import {withToolbar} from '../../containers/Toolbar';
import {withSidebar} from '../../containers/Sidebar';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {AttributeMap, Route, UpdateRouteMethod} from '../../services/Router/types';
import ResourceStore from '../../stores/ResourceStore';
import {translate} from '../../utils/Translator';
import toolbarActionRegistry from './registries/ToolbarActionRegistry';
import formStyles from './form.scss';

type Props = ViewProps & {
    locales: Array<string>,
    resourceStore: ResourceStore,
};

@observer
class Form extends React.Component<Props> {
    resourceStore: ResourceStore;
    resourceFormStore: ResourceFormStore;
    form: ?ElementRef<typeof FormContainer>;
    @observable errors = [];
    showSuccess: IObservableValue<boolean> = observable.box(false);
    @observable toolbarActions = [];
    @observable hasPreview: boolean = false;
    @observable showDirtyWarning = false;
    postponedUpdateRouteMethod: ?UpdateRouteMethod;
    postponedRoute: ?Route;
    postponedRouteAttributes: ?AttributeMap;

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
                    apiOptions = {},
                    formKey,
                    idQueryParameter,
                    resourceKey,
                    routerAttributesToFormStore = {},
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

        if (!formKey) {
            throw new Error('The route does not define the mandatory "formKey" option');
        }

        const formStoreOptions = this.buildFormStoreOptions(apiOptions, attributes, routerAttributesToFormStore);

        if (this.hasOwnResourceStore) {
            let locale = resourceStore.locale;
            if (!locale && this.locales) {
                locale = observable.box();
            }

            if (idQueryParameter) {
                this.resourceStore = new ResourceStore(resourceKey, id, {locale}, formStoreOptions, idQueryParameter);
            } else {
                this.resourceStore = new ResourceStore(resourceKey, id, {locale}, formStoreOptions);
            }
        } else {
            this.resourceStore = resourceStore;
        }

        this.resourceFormStore = new ResourceFormStore(this.resourceStore, formKey, formStoreOptions);

        if (this.resourceStore.locale) {
            router.bind('locale', this.resourceStore.locale);
        }

        router.addUpdateRouteHook(this.checkFormStoreDirtyStateBeforeNavigation);
    }

    @action checkFormStoreDirtyStateBeforeNavigation = (
        route: ?Route,
        attributes: ?AttributeMap,
        updateRouteMethod: ?UpdateRouteMethod
    ) => {
        if (!this.resourceFormStore.dirty) {
            return true;
        }

        if (
            this.showDirtyWarning === true
            && this.postponedRoute === route
            && equals(this.postponedRouteAttributes, attributes)
            && this.postponedUpdateRouteMethod === updateRouteMethod
        ) {
            // If the warning has already been displayed for the exact same route and attributes we can assume that the
            // confirm button in the warning has been clicked, since it calls the same routing action again.
            return true;
        }

        if (!route && !attributes && !updateRouteMethod) {
            // If none of these attributes are set the call comes because the user wants to close the window
            return false;
        }

        this.showDirtyWarning = true;
        this.postponedUpdateRouteMethod = updateRouteMethod;
        this.postponedRoute = route;
        this.postponedRouteAttributes = attributes;

        return false;
    };

    buildFormStoreOptions(
        apiOptions: Object,
        attributes: Object,
        routerAttributesToFormStore: {[string | number]: string}
    ) {
        const formStoreOptions = apiOptions ? apiOptions : {};

        routerAttributesToFormStore = toJS(routerAttributesToFormStore);
        Object.keys(routerAttributesToFormStore).forEach((key) => {
            const attributeName = routerAttributesToFormStore[key];
            const formOptionKey = isNaN(key) ? key : routerAttributesToFormStore[key];

            formStoreOptions[formOptionKey] = attributes[attributeName];
        });

        return formStoreOptions;
    }

    @action componentDidMount() {
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
            this.resourceFormStore,
            this,
            router,
            this.locales
        ));

        when(() => !this.resourceFormStore.loading, this.evaluatePreview);
    }

    componentDidUpdate(prevProps: Props) {
        if (!equals(this.props.locales, prevProps.locales)) {
            this.toolbarActions.forEach((toolbarAction) => {
                toolbarAction.setLocales(this.locales);
            });
        }
    }

    componentWillUnmount() {
        const {router} = this.props;
        router.removeUpdateRouteHook(this.checkFormStoreDirtyStateBeforeNavigation);

        this.resourceFormStore.destroy();

        if (this.hasOwnResourceStore) {
            this.resourceStore.destroy();
        }
    }

    @action setHasPreview = (hasPreview: boolean) => {
        this.hasPreview = hasPreview;
    };

    @action showSuccessSnackbar = () => {
        this.showSuccess.set(true);
    };

    @action submit = (action: ?string) => {
        if (!this.form) {
            throw new Error('The form ref has not been set! This should not happen and is likely a bug.');
        }
        this.form.submit(action);
    };

    evaluatePreview = (): void => {
        const {
            router: {
                route: {
                    options: {
                        preview,
                    },
                },
            },
        } = this.props;

        if (!preview) {
            this.setHasPreview(false);

            return;
        }

        jexl.eval(preview, this.resourceStore.data).then(this.setHasPreview);
    };

    handleSubmit = (actionParameter: ?string) => {
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

        return this.resourceFormStore.save(saveOptions)
            .then((response) => {
                this.showSuccessSnackbar();
                this.evaluatePreview();

                if (editRoute) {
                    router.navigate(
                        editRoute,
                        {
                            id: resourceStore.id,
                            locale: resourceStore.locale,
                            ...editRouteParameters,
                        }
                    );
                }

                return response;
            })
            .catch(action((error) => {
                this.errors.push(error);
            }));
    };

    handleError = () => {
        this.errors.push('Errors occured when trying to save the data from the FormStore');
    };

    @action handleDirtyWarningCancelClick = () => {
        this.showDirtyWarning = false;
        this.postponedUpdateRouteMethod = undefined;
        this.postponedRoute = undefined;
        this.postponedRouteAttributes = undefined;
    };

    @action handleDirtyWarningConfirmClick = () => {
        if (!this.postponedUpdateRouteMethod || !this.postponedRoute || !this.postponedRouteAttributes) {
            throw new Error('Some routing information is missing. This should not happen and is likely a bug.');
        }

        this.postponedUpdateRouteMethod(this.postponedRoute.name, this.postponedRouteAttributes);
        this.postponedUpdateRouteMethod = undefined;
        this.postponedRoute = undefined;
        this.postponedRouteAttributes = undefined;
    };

    setFormRef = (form: ?ElementRef<typeof FormContainer>) => {
        this.form = form;
    };

    render() {
        return (
            <div className={formStyles.form}>
                <FormContainer
                    onError={this.handleError}
                    onSubmit={this.handleSubmit}
                    ref={this.setFormRef}
                    store={this.resourceFormStore}
                />
                {this.toolbarActions.map((toolbarAction) => toolbarAction.getNode())}
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmText={translate('sulu_admin.confirm')}
                    onCancel={this.handleDirtyWarningCancelClick}
                    onConfirm={this.handleDirtyWarningConfirmClick}
                    open={this.showDirtyWarning}
                    title={translate('sulu_admin.dirty_warning_dialog_title')}
                >
                    {translate('sulu_admin.dirty_warning_dialog_text')}
                </Dialog>
            </div>
        );
    }
}

const FormWithToolbar = withToolbar(Form, function() {
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

    const icons = [];
    const formData = this.resourceFormStore.data;

    if (formData.hasOwnProperty('publishedState') || formData.hasOwnProperty('published')) {
        const {publishedState, published} = formData;
        icons.push(
            <PublishIndicator
                draft={publishedState === undefined ? false : !publishedState}
                key="publish"
                published={published === undefined ? false : !!published}
            />
        );
    }

    return {
        backButton,
        errors,
        locale,
        items,
        icons,
        showSuccess,
    };
});

export default withSidebar(FormWithToolbar, function() {
    return this.hasPreview ? {
        view: 'sulu_preview.preview',
        sizes: ['medium', 'large'],
        props: {
            router: this.props.router,
            formStore: this.resourceFormStore,
        },
    } : null;
});
