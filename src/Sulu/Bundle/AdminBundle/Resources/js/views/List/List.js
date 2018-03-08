// @flow
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import React from 'react';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import ResourceRequester from '../../services/ResourceRequester';
import {translate} from '../../utils/Translator';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';
import listStyles from './list.scss';

@observer
class List extends React.Component<ViewProps> {
    page: IObservableValue<number> = observable();
    locale: IObservableValue<string> = observable();
    datagridStore: DatagridStore;
    @observable deleting = false;

    componentWillMount() {
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
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
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
            <div className={listStyles.list}>
                {title && <h1>{translate(title)}</h1>}
                <Datagrid
                    store={this.datagridStore}
                    adapters={adapters}
                    onItemClick={editRoute && this.handleEditClick}
                />
            </div>
        );
    }
}

export default withToolbar(List, function() {
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
            icon: 'su-add',
            onClick: () => {
                router.navigate(addRoute, {locale: this.locale.get()});
            },
        });
    }

    items.push({
        type: 'button',
        value: translate('sulu_admin.delete'),
        icon: 'su-trash',
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
