// @flow
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {action, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import type {ElementRef} from 'react';
import React from 'react';
import equals from 'fast-deep-equal';
import {default as DatagridContainer, DatagridStore} from '../../containers/Datagrid';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {Route} from '../../services/Router/types';
import {translate} from '../../utils/Translator';
import toolbarActionRegistry from './registries/ToolbarActionRegistry';
import datagridStyles from './datagrid.scss';

const USER_SETTINGS_KEY = 'datagrid';

type Props = ViewProps & {
    onItemAdd?: (parentId: string | number) => void,
    onItemClick?: (itemId: string | number) => void,
};

@observer
class Datagrid extends React.Component<Props> {
    page: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    datagridStore: DatagridStore;
    datagrid: ?ElementRef<typeof DatagridContainer>;
    @observable toolbarActions = [];

    static getDerivedRouteAttributes(route: Route) {
        const {
            options: {
                resourceKey,
            },
        } = route;

        return {
            active: DatagridStore.getActiveSetting(resourceKey, USER_SETTINGS_KEY),
            sortColumn: DatagridStore.getSortColumnSetting(resourceKey, USER_SETTINGS_KEY),
            sortOrder: DatagridStore.getSortOrderSetting(resourceKey, USER_SETTINGS_KEY),
            limit: DatagridStore.getLimitSetting(resourceKey, USER_SETTINGS_KEY),
        };
    }

    constructor(props: Props) {
        super(props);

        const router = this.props.router;
        const {
            attributes,
            route: {
                options: {
                    adapters,
                    apiOptions = {},
                    datagridKey,
                    locales,
                    resourceKey,
                    routerAttributesToDatagridStore = {},
                },
            },
        } = router;

        if (!resourceKey) {
            throw new Error('The route does not define the mandatory "resourceKey" option');
        }

        if (!datagridKey) {
            throw new Error('The route does not define the mandatory "datagridKey" option');
        }

        if (!adapters) {
            throw new Error('The route does not define the mandatory "adapters" option');
        }

        const observableOptions = {};

        router.bind('page', this.page, 1);
        observableOptions.page = this.page;

        if (locales) {
            router.bind('locale', this.locale);
            observableOptions.locale = this.locale;
        }

        const datagridStoreOptions = this.buildDatagridStoreOptions(
            apiOptions,
            attributes,
            routerAttributesToDatagridStore
        );

        this.datagridStore = new DatagridStore(
            resourceKey,
            datagridKey,
            USER_SETTINGS_KEY,
            observableOptions,
            datagridStoreOptions
        );

        router.bind('active', this.datagridStore.active);
        router.bind('sortColumn', this.datagridStore.sortColumn);
        router.bind('sortOrder', this.datagridStore.sortOrder);
        router.bind('search', this.datagridStore.searchTerm);
        router.bind('limit', this.datagridStore.limit, 10);
    }

    buildDatagridStoreOptions(
        apiOptions: Object,
        attributes: Object,
        routerAttributesToDatagridStore: {[string | number]: string}
    ) {
        const datagridStoreOptions = apiOptions ? apiOptions : {};

        routerAttributesToDatagridStore = toJS(routerAttributesToDatagridStore);
        Object.keys(routerAttributesToDatagridStore).forEach((key) => {
            const attributeName = routerAttributesToDatagridStore[key];
            const datagridOptionKey = isNaN(key) ? key : routerAttributesToDatagridStore[key];

            datagridStoreOptions[datagridOptionKey] = attributes[attributeName];
        });

        return datagridStoreOptions;
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
            this.datagridStore,
            this,
            router,
            locales
        ));
    }

    componentDidUpdate(prevProps: ViewProps) {
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
        this.datagridStore.destroy();
    }

    handleItemAdd = (parentId: string | number) => {
        const {onItemAdd, router} = this.props;

        if (onItemAdd) {
            onItemAdd(parentId);
            return;
        }

        router.navigate(router.route.options.addRoute, {locale: this.locale.get(), parentId: parentId});
    };

    handleItemClick = (itemId: string | number) => {
        const {onItemClick, router} = this.props;

        if (onItemClick) {
            onItemClick(itemId);
            return;
        }

        router.navigate(router.route.options.editRoute, {id: itemId, locale: this.locale.get()});
    };

    requestSelectionDelete = () => {
        if (!this.datagrid) {
            throw new Error('Datagrid not created yet.');
        }

        this.datagrid.requestSelectionDelete();
    };

    setDatagridRef = (datagrid: ?ElementRef<typeof DatagridContainer>) => {
        this.datagrid = datagrid;
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
                <DatagridContainer
                    adapters={adapters}
                    header={title && <h1 className={datagridStyles.header}>{translate(title)}</h1>}
                    onItemAdd={onItemAdd || addRoute ? this.handleItemAdd : undefined}
                    onItemClick={onItemClick || editRoute ? this.handleItemClick : undefined}
                    ref={this.setDatagridRef}
                    searchable={searchable}
                    store={this.datagridStore}
                />
                {this.toolbarActions.map((toolbarAction) => toolbarAction.getNode())}
            </div>
        );
    }
}

export default withToolbar(Datagrid, function() {
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
        locale,
        items,
    };
});
