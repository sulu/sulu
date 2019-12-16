// @flow
import type {IObservableValue} from 'mobx';
import {action, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import type {ElementRef} from 'react';
import React, {Fragment} from 'react';
import equals from 'fast-deep-equal';
import {default as ListContainer, ListStore} from '../../containers/List';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {Route} from '../../services/Router/types';
import {translate} from '../../utils/Translator';
import ResourceStore from '../../stores/ResourceStore';
import listToolbarActionRegistry from './registries/listToolbarActionRegistry';
import AbstractListToolbarAction from './toolbarActions/AbstractListToolbarAction';
import listStyles from './list.scss';

const DEFAULT_USER_SETTINGS_KEY = 'list';
const DEFAULT_LIMIT = 10;

type Props = ViewProps & {
    locale?: IObservableValue<string>,
    locales?: Array<string>,
    onItemAdd?: (parentId: ?string | number) => void,
    onItemClick?: (itemId: string | number) => void,
    resourceStore?: ResourceStore,
    title?: string,
};

@observer
class List extends React.Component<Props> {
    page: IObservableValue<number> = observable.box();
    locale: IObservableValue<string>;
    listStore: ListStore;
    list: ?ElementRef<typeof ListContainer>;

    @observable toolbarActions: Array<AbstractListToolbarAction> = [];
    @observable errors = [];

    static getDerivedRouteAttributes(route: Route) {
        const {
            options: {
                listKey,
                userSettingsKey = DEFAULT_USER_SETTINGS_KEY,
            },
        } = route;

        const limit = ListStore.getLimitSetting(listKey, userSettingsKey);

        return {
            active: ListStore.getActiveSetting(listKey, userSettingsKey),
            sortColumn: ListStore.getSortColumnSetting(listKey, userSettingsKey),
            sortOrder: ListStore.getSortOrderSetting(listKey, userSettingsKey),
            limit: limit === DEFAULT_LIMIT ? undefined : limit,
        };
    }

    @computed get locales() {
        const {
            locales: propsLocales,
            router: {
                route: {
                    options: {
                        locales: routeLocales,
                    },
                },
            },
        } = this.props;

        return routeLocales ? routeLocales : propsLocales;
    }

    constructor(props: Props) {
        super(props);

        const {locale, router} = this.props;
        const {
            attributes,
            route: {
                options: {
                    adapters,
                    requestParameters = {},
                    listKey,
                    resourceKey,
                    routerAttributesToListRequest = {},
                    resourceStorePropertiesToListRequest = {},
                    userSettingsKey = DEFAULT_USER_SETTINGS_KEY,
                    routerAttributesToListMetadata = {},
                    resourceStorePropertiesToListMetadata = {},
                },
            },
        } = router;

        if (!resourceKey) {
            throw new Error('The route does not define the mandatory "resourceKey" option');
        }

        if (!listKey) {
            throw new Error('The route does not define the mandatory "listKey" option');
        }

        if (!adapters) {
            throw new Error('The route does not define the mandatory "adapters" option');
        }

        this.locale = locale ? locale : observable.box();

        const observableOptions = {};

        router.bind('page', this.page, 1);
        observableOptions.page = this.page;

        if (this.locales) {
            router.bind('locale', this.locale);
            observableOptions.locale = this.locale;
        }

        const listStoreOptions = this.buildListStoreOptions(
            requestParameters,
            attributes,
            routerAttributesToListRequest,
            resourceStorePropertiesToListRequest,
            props.resourceStore
        );

        const metadataOptions = this.buildMetadataOptions(
            attributes,
            routerAttributesToListMetadata,
            resourceStorePropertiesToListMetadata,
            props.resourceStore
        );

        this.listStore = new ListStore(
            resourceKey,
            listKey,
            userSettingsKey,
            observableOptions,
            listStoreOptions,
            metadataOptions
        );

        router.bind('active', this.listStore.active);
        router.bind('sortColumn', this.listStore.sortColumn);
        router.bind('sortOrder', this.listStore.sortOrder);
        router.bind('search', this.listStore.searchTerm);
        router.bind('limit', this.listStore.limit, DEFAULT_LIMIT);
    }

    buildMetadataOptions(
        attributes: Object,
        routerAttributesToListMetadata: {[string | number]: string},
        resourceStorePropertiesToListMetadata: {[string | number]: string},
        resourceStore: ?ResourceStore
    ) {
        const metadataOptions = {};
        routerAttributesToListMetadata = toJS(routerAttributesToListMetadata);

        Object.keys(routerAttributesToListMetadata).forEach((key) => {
            const listOptionKey = routerAttributesToListMetadata[key];
            const attributeName = isNaN(key) ? key : routerAttributesToListMetadata[key];

            metadataOptions[listOptionKey] = attributes[attributeName];
        });

        resourceStorePropertiesToListMetadata = toJS(resourceStorePropertiesToListMetadata);
        Object.keys(resourceStorePropertiesToListMetadata).forEach((key) => {
            const listMetadataKey = resourceStorePropertiesToListMetadata[key];
            const attributeName = isNaN(key) ? key : resourceStorePropertiesToListMetadata[key];

            if (!resourceStore || !resourceStore.data) {
                return;
            }

            metadataOptions[listMetadataKey] = resourceStore.data[attributeName];
        });

        return metadataOptions;
    }

    buildListStoreOptions(
        requestParameters: Object,
        attributes: Object,
        routerAttributesToListRequest: {[string | number]: string},
        resourceStorePropertiesToListRequest: {[string | number]: string},
        resourceStore: ?ResourceStore
    ) {
        const listStoreOptions = requestParameters ? requestParameters : {};
        routerAttributesToListRequest = toJS(routerAttributesToListRequest);
        Object.keys(routerAttributesToListRequest).forEach((key) => {
            const listOptionKey = routerAttributesToListRequest[key];
            const attributeName = isNaN(key) ? key : routerAttributesToListRequest[key];

            listStoreOptions[listOptionKey] = attributes[attributeName];
        });

        resourceStorePropertiesToListRequest = toJS(resourceStorePropertiesToListRequest);
        Object.keys(resourceStorePropertiesToListRequest).forEach((key) => {
            const listOptionKey = resourceStorePropertiesToListRequest[key];
            const attributeName = isNaN(key) ? key : resourceStorePropertiesToListRequest[key];

            if (!resourceStore || !resourceStore.data) {
                return;
            }

            listStoreOptions[listOptionKey] = resourceStore.data[attributeName];
        });

        return listStoreOptions;
    }

    @action componentDidMount() {
        const {resourceStore, router} = this.props;
        const {
            route: {
                options: {
                    locales,
                    toolbarActions: rawToolbarActions,
                },
            },
        } = router;

        const toolbarActions = toJS(rawToolbarActions);

        if (!toolbarActions) {
            return;
        }

        toolbarActions.forEach((toolbarAction) => {
            if (typeof toolbarAction !== 'object') {
                throw new Error(
                    'The value of a toolbarAction entry must be an object, but ' + typeof toolbarAction + ' was given!'
                );
            }
        });

        this.toolbarActions = toolbarActions
            .map((toolbarAction): AbstractListToolbarAction => new (listToolbarActionRegistry.get(toolbarAction.type))(
                this.listStore,
                this,
                router,
                locales,
                resourceStore,
                toolbarAction.options
            ));
    }

    componentDidUpdate(prevProps: Props) {
        const {
            route: {
                options: {
                    locales,
                },
            },
        } = this.props.router;

        const {
            route: {
                options: {
                    prevLocales,
                },
            },
        } = prevProps.router;

        if (!equals(locales, prevLocales)) {
            this.toolbarActions.forEach((toolbarAction) => {
                toolbarAction.setLocales(locales);
            });
        }
    }

    componentWillUnmount() {
        this.listStore.destroy();
    }

    addItem = (parentId: ?string | number) => {
        const {onItemAdd, router} = this.props;
        const {
            route: {
                options: {
                    addView,
                },
            },
        } = router;

        if (onItemAdd) {
            onItemAdd(parentId);
            return;
        }

        router.navigate(addView, {locale: this.locale.get(), parentId});
    };

    handleItemClick = (itemId: string | number) => {
        const {onItemClick, router} = this.props;
        const {
            route: {
                options: {
                    editView,
                },
            },
        } = router;

        if (onItemClick) {
            onItemClick(itemId);
            return;
        }

        router.navigate(editView, {id: itemId, locale: this.locale.get()});
    };

    requestSelectionDelete = (allowConflictDelete: boolean = true) => {
        if (!this.list) {
            throw new Error('List not created yet.');
        }

        this.list.requestSelectionDelete(allowConflictDelete);
    };

    reload = () => {
        this.listStore.reload();
    };

    setListRef = (list: ?ElementRef<typeof ListContainer>) => {
        this.list = list;
    };

    render() {
        const {
            onItemAdd,
            onItemClick,
            router: {
                route: {
                    options: {
                        adapters,
                        addView,
                        editView,
                        searchable,
                        title: routeTitle,
                    },
                },
            },
            title: propTitle,
        } = this.props;

        const title = routeTitle ? translate(routeTitle) : propTitle;

        return (
            <Fragment>
                <ListContainer
                    adapters={adapters}
                    header={title && <h1 className={listStyles.header}>{title}</h1>}
                    onItemAdd={onItemAdd || addView ? this.addItem : undefined}
                    onItemClick={onItemClick || editView ? this.handleItemClick : undefined}
                    ref={this.setListRef}
                    searchable={searchable}
                    store={this.listStore}
                />
                {this.toolbarActions.map((toolbarAction) => toolbarAction.getNode())}
            </Fragment>
        );
    }
}

export default withToolbar(List, function() {
    const {errors} = this;
    const {router} = this.props;

    const {
        route: {
            options: {
                backView,
            },
        },
    } = router;

    const backButton = backView
        ? {
            onClick: () => {
                const options = {};
                if (this.locale) {
                    options.locale = this.locale.get();
                }
                router.restore(backView, options);
            },
        }
        : undefined;
    const locale = this.locales
        ? {
            value: this.locale.get(),
            onChange: action((locale) => {
                this.locale.set(locale);
            }),
            options: this.locales.map((locale) => ({
                value: locale,
                label: locale,
            })),
        }
        : undefined;

    const items = this.toolbarActions
        .map((toolbarAction) => toolbarAction.getToolbarItemConfig())
        .filter((item) => item != null);

    return {
        backButton,
        errors,
        locale,
        items,
    };
});
