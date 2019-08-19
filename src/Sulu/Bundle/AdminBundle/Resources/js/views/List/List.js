// @flow
import type {IObservableValue} from 'mobx';
import {action, observable, toJS} from 'mobx';
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
import listToolbarActionRegistry from './registries/ListToolbarActionRegistry';
import AbstractListToolbarAction from './toolbarActions/AbstractListToolbarAction';
import listStyles from './list.scss';

const DEFAULT_USER_SETTINGS_KEY = 'list';
const DEFAULT_LIMIT = 10;

type Props = ViewProps & {
    locale?: IObservableValue<string>,
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

    constructor(props: Props) {
        super(props);

        const {locale, router} = this.props;
        const {
            attributes,
            route: {
                options: {
                    adapters,
                    apiOptions = {},
                    listKey,
                    locales,
                    resourceKey,
                    routerAttributesToListStore = {},
                    resourceStorePropertiesToListStore = {},
                    userSettingsKey = DEFAULT_USER_SETTINGS_KEY,
                    routerAttributesToListMetadata = {},
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

        if (locales) {
            router.bind('locale', this.locale);
            observableOptions.locale = this.locale;
        }

        const listStoreOptions = this.buildListStoreOptions(
            apiOptions,
            attributes,
            routerAttributesToListStore,
            resourceStorePropertiesToListStore,
            props.resourceStore
        );

        const metadataOptions = this.buildMetadataOptions(
            attributes,
            routerAttributesToListMetadata
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
        routerAttributesToListMetadata: {[string | number]: string}
    ) {
        const metadataOptions = {};
        routerAttributesToListMetadata = toJS(routerAttributesToListMetadata);

        Object.keys(routerAttributesToListMetadata).forEach((key) => {
            const listOptionKey = routerAttributesToListMetadata[key];
            const attributeName = isNaN(key) ? key : routerAttributesToListMetadata[key];

            metadataOptions[listOptionKey] = attributes[attributeName];
        });

        return metadataOptions;
    }

    buildListStoreOptions(
        apiOptions: Object,
        attributes: Object,
        routerAttributesToListStore: {[string | number]: string},
        resourceStorePropertiesToListStore: {[string | number]: string},
        resourceStore: ?ResourceStore
    ) {
        const listStoreOptions = apiOptions ? apiOptions : {};
        routerAttributesToListStore = toJS(routerAttributesToListStore);
        Object.keys(routerAttributesToListStore).forEach((key) => {
            const listOptionKey = routerAttributesToListStore[key];
            const attributeName = isNaN(key) ? key : routerAttributesToListStore[key];

            listStoreOptions[listOptionKey] = attributes[attributeName];
        });

        resourceStorePropertiesToListStore = toJS(resourceStorePropertiesToListStore);
        Object.keys(resourceStorePropertiesToListStore).forEach((key) => {
            const listOptionKey = resourceStorePropertiesToListStore[key];
            const attributeName = isNaN(key) ? key : resourceStorePropertiesToListStore[key];

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

        Object.keys(toolbarActions).forEach((toolbarActionKey) => {
            const toolbarActionValue = toolbarActions[toolbarActionKey];
            if (typeof toolbarActionValue !== 'object') {
                throw new Error(
                    'The value of the toolbarAction entry "' + toolbarActionKey + '" must be an object, '
                    + 'but ' + typeof toolbarActionValue + ' was given!'
                );
            }
        });

        this.toolbarActions = Object.keys(toolbarActions)
            .map((toolbarActionKey): AbstractListToolbarAction => new (listToolbarActionRegistry.get(toolbarActionKey))(
                this.listStore,
                this,
                router,
                locales,
                resourceStore,
                toolbarActions[toolbarActionKey]
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
                    addRoute,
                },
            },
        } = router;

        if (onItemAdd) {
            onItemAdd(parentId);
            return;
        }

        router.navigate(addRoute, {locale: this.locale.get(), parentId});
    };

    handleItemClick = (itemId: string | number) => {
        const {onItemClick, router} = this.props;
        const {
            route: {
                options: {
                    editRoute,
                },
            },
        } = router;

        if (onItemClick) {
            onItemClick(itemId);
            return;
        }

        router.navigate(editRoute, {id: itemId, locale: this.locale.get()});
    };

    requestSelectionDelete = () => {
        if (!this.list) {
            throw new Error('List not created yet.');
        }

        this.list.requestSelectionDelete();
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
                        addRoute,
                        editRoute,
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
                    onItemAdd={onItemAdd || addRoute ? this.addItem : undefined}
                    onItemClick={onItemClick || editRoute ? this.handleItemClick : undefined}
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
                backRoute,
                locales,
            },
        },
    } = router;

    const backButton = backRoute
        ? {
            onClick: () => {
                const options = {};
                if (this.locale) {
                    options.locale = this.locale.get();
                }
                router.restore(backRoute, options);
            },
        }
        : undefined;
    const locale = locales
        ? {
            value: this.locale.get(),
            onChange: action((locale) => {
                this.locale.set(locale);
            }),
            options: locales.map((locale) => ({
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
