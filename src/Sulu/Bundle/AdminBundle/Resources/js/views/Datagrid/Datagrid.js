// @flow
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import React from 'react';
import {default as DatagridContainer} from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {Route} from '../../services/Router/types';
import userStore from '../../stores/UserStore';
import {translate} from '../../utils/Translator';
import datagridStyles from './datagrid.scss';

const USER_SETTING_PREFIX = 'sulu_admin.datagrid';
const USER_SETTING_ACTIVE = 'active';
const USER_SETTING_SORT_COLUMN = 'sort_column';
const USER_SETTING_SORT_ORDER = 'sort_order';

function getActiveSettingKey(resourceKey): string {
    return [USER_SETTING_PREFIX, resourceKey, USER_SETTING_ACTIVE].join('.');
}

function getSortColumnSettingKey(resourceKey): string {
    return [USER_SETTING_PREFIX, resourceKey, USER_SETTING_SORT_COLUMN].join('.');
}

function getSortOrderSettingKey(resourceKey): string {
    return [USER_SETTING_PREFIX, resourceKey, USER_SETTING_SORT_ORDER].join('.');
}

@observer
class Datagrid extends React.Component<ViewProps> {
    page: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    datagridStore: DatagridStore;
    @observable deleting = false;
    activeDisposer: () => void;
    sortColumnDisposer: () => void;
    sortOrderDisposer: () => void;

    static getDerivedRouteAttributes(route: Route) {
        const {
            options: {
                resourceKey,
            },
        } = route;

        return {
            active: userStore.getPersistentSetting(getActiveSettingKey(resourceKey)),
            sortColumn: userStore.getPersistentSetting(getSortColumnSettingKey(resourceKey)),
            sortOrder: userStore.getPersistentSetting(getSortOrderSettingKey(resourceKey)),
        };
    }

    constructor(props: ViewProps) {
        super(props);

        const router = this.props.router;
        const {
            route: {
                options: {
                    adapters,
                    apiOptions,
                    locales,
                    resourceKey,
                },
            },
        } = router;

        if (!resourceKey) {
            throw new Error('The route does not define the mandatory resourceKey option');
        }

        if (!adapters) {
            throw new Error('The route does not define the mandatory adapters option');
        }

        const observableOptions = {};

        router.bind('page', this.page, 1);
        observableOptions.page = this.page;

        if (locales) {
            router.bind('locale', this.locale);
            observableOptions.locale = this.locale;
        }

        this.datagridStore = new DatagridStore(resourceKey, observableOptions, apiOptions);
        router.bind('active', this.datagridStore.active);

        router.bind('sortColumn', this.datagridStore.sortColumn);
        router.bind('sortOrder', this.datagridStore.sortOrder);
        router.bind('search', this.datagridStore.searchTerm);

        const {
            active,
            sortColumn,
            sortOrder,
        } = this.datagridStore;

        this.sortColumnDisposer = autorun(
            () => userStore.setPersistentSetting(getSortColumnSettingKey(resourceKey), sortColumn.get())
        );
        this.sortOrderDisposer = autorun(
            () => userStore.setPersistentSetting(getSortOrderSettingKey(resourceKey), sortOrder.get())
        );
        this.activeDisposer = autorun(
            () => {
                const activeValue = active.get();
                if (activeValue) {
                    userStore.setPersistentSetting(getActiveSettingKey(resourceKey), activeValue);
                }
            }
        );
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
        this.activeDisposer();
        this.sortColumnDisposer();
        this.sortOrderDisposer();
    }

    handleItemAdd = (rowId) => {
        const {router} = this.props;
        const {
            route: {
                options: {
                    addRoute,
                },
            },
        } = router;

        router.navigate(addRoute, {locale: this.locale.get(), parentId: rowId});
    };

    handleEditClick = (rowId) => {
        const {router} = this.props;
        router.navigate(router.route.options.editRoute, {id: rowId, locale: this.locale.get()});
    };

    render() {
        const {
            route: {
                options: {
                    adapters,
                    addRoute,
                    editRoute,
                    searchable,
                    title,
                },
            },
        } = this.props.router;

        return (
            <div className={datagridStyles.datagrid}>
                <DatagridContainer
                    adapters={adapters}
                    header={title && <h1 className={datagridStyles.header}>{translate(title)}</h1>}
                    onItemAdd={addRoute && this.handleItemAdd}
                    onItemClick={editRoute && this.handleEditClick}
                    searchable={searchable}
                    store={this.datagridStore}
                />
            </div>
        );
    }
}

export default withToolbar(Datagrid, function() {
    const {router} = this.props;

    const {
        route: {
            options: {
                addRoute,
                locales,
            },
        },
    } = router;

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

    const items = [];

    if (addRoute) {
        items.push({
            type: 'button',
            value: translate('sulu_admin.add'),
            icon: 'su-plus-circle',
            onClick: this.handleItemAdd,
        });
    }

    items.push({
        type: 'button',
        value: translate('sulu_admin.delete'),
        icon: 'su-trash-alt',
        disabled: this.datagridStore.selectionIds.length === 0,
        loading: this.deleting,
        onClick: action(() => {
            this.deleting = true;

            return this.datagridStore.deleteSelection()
                .then(action(() => {
                    this.deleting = false;
                }))
                .catch(action((error) => {
                    this.deleting = false;
                    return Promise.reject(error);
                }));
        }),
    });

    return {
        locale,
        items,
    };
});
