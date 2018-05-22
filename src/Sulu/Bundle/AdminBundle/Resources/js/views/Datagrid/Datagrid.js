// @flow
import {action, autorun, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import React from 'react';
import {default as DatagridContainer} from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import ResourceRequester from '../../services/ResourceRequester';
import type {Route} from '../../services/Router/types';
import userStore from '../../stores/UserStore';
import {translate} from '../../utils/Translator';
import datagridStyles from './datagrid.scss';

const USER_SETTING_PREFIX = 'sulu_admin.datagrid';
const USER_SETTING_SORT_COLUMN = 'sort_column';
const USER_SETTING_SORT_ORDER = 'sort_order';

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
    sortColumnDisposer: () => void;
    sortOrderDisposer: () => void;

    static getDerivedRouteAttributes(route: Route) {
        const {
            options: {
                resourceKey,
            },
        } = route;

        return {
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

        router.bind('sortColumn', this.datagridStore.sortColumn);
        router.bind('sortOrder', this.datagridStore.sortOrder);

        const {
            sortColumn,
            sortOrder,
        } = this.datagridStore;

        this.sortColumnDisposer = autorun(
            () => userStore.setPersistentSetting(getSortColumnSettingKey(resourceKey), sortColumn.get())
        );
        this.sortOrderDisposer = autorun(
            () => userStore.setPersistentSetting(getSortOrderSettingKey(resourceKey), sortOrder.get())
        );
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
        this.sortColumnDisposer();
        this.sortOrderDisposer();
    }

    handleEditClick = (rowId) => {
        const {router} = this.props;
        router.navigate(router.route.options.editRoute, {id: rowId, locale: this.locale.get()});
    };

    render() {
        const {
            route: {
                options: {
                    adapters,
                    title,
                    editRoute,
                },
            },
        } = this.props.router;

        return (
            <div className={datagridStyles.datagrid}>
                {title && <h1>{translate(title)}</h1>}
                <DatagridContainer
                    adapters={adapters}
                    onItemClick={editRoute && this.handleEditClick}
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
                resourceKey,
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
            onClick: () => {
                router.navigate(addRoute, {locale: this.locale.get()});
            },
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

            const deletePromises = [];
            this.datagridStore.selectionIds.forEach((id) => {
                deletePromises.push(ResourceRequester.delete(resourceKey, id));
            });

            return Promise.all(deletePromises).then(action(() => {
                this.datagridStore.clearSelection();
                this.datagridStore.sendRequest();
                this.deleting = false;
            }));
        }),
    });

    return {
        locale,
        items,
    };
});
