// @flow
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {action, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import type {ElementRef} from 'react';
import React from 'react';
import equals from 'fast-deep-equal';
import {default as ListContainer, ListStore} from '../../containers/List';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {Route} from '../../services/Router/types';
import {translate} from '../../utils/Translator';
import ResourceStore from '../../stores/ResourceStore';
import toolbarActionRegistry from './registries/ToolbarActionRegistry';
import listStyles from './list.scss';

const USER_SETTINGS_KEY = 'list';

type Props = ViewProps & {
    locale?: IObservableValue<string>,
    resourceStore?: ResourceStore,
    onItemAdd?: (parentId: string | number) => void,
    onItemClick?: (itemId: string | number) => void,
};

@observer
class List extends React.Component<Props> {
    page: IObservableValue<number> = observable.box();
    locale: IObservableValue<string>;
    listStore: ListStore;
    list: ?ElementRef<typeof ListContainer>;

    @observable toolbarActions = [];
    @observable errors = [];

    static getDerivedRouteAttributes(route: Route) {
        const {
            options: {
                resourceKey,
            },
        } = route;

        return {
            active: ListStore.getActiveSetting(resourceKey, USER_SETTINGS_KEY),
            sortColumn: ListStore.getSortColumnSetting(resourceKey, USER_SETTINGS_KEY),
            sortOrder: ListStore.getSortOrderSetting(resourceKey, USER_SETTINGS_KEY),
            limit: ListStore.getLimitSetting(resourceKey, USER_SETTINGS_KEY),
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

        this.listStore = new ListStore(
            resourceKey,
            listKey,
            USER_SETTINGS_KEY,
            observableOptions,
            listStoreOptions
        );

        router.bind('active', this.listStore.active);
        router.bind('sortColumn', this.listStore.sortColumn);
        router.bind('sortOrder', this.listStore.sortOrder);
        router.bind('search', this.listStore.searchTerm);
        router.bind('limit', this.listStore.limit, 10);
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
        const {router} = this.props;
        const {
            route: {
                options: {
                    locales,
                    toolbarActions,
                },
            },
        } = router;

        if (!toolbarActions) {
            return;
        }

        this.toolbarActions = toolbarActions.map((toolbarAction) => new (toolbarActionRegistry.get(toolbarAction))(
            this.listStore,
            this,
            router,
            locales
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

    addItem = (parentId: string | number) => {
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
                        title,
                    },
                },
            },
        } = this.props;

        return (
            <div>
                <ListContainer
                    adapters={adapters}
                    header={title && <h1 className={listStyles.header}>{translate(title)}</h1>}
                    onItemAdd={onItemAdd || addRoute ? this.addItem : undefined}
                    onItemClick={onItemClick || editRoute ? this.handleItemClick : undefined}
                    ref={this.setListRef}
                    searchable={searchable}
                    store={this.listStore}
                />
                {this.toolbarActions.map((toolbarAction) => toolbarAction.getNode())}
            </div>
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
        .filter((item) => item !== undefined);

    return {
        backButton,
        errors,
        locale,
        items,
    };
});
