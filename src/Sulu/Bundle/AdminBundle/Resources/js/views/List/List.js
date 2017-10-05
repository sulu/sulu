// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import ResourceRequester from '../../services/ResourceRequester';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer';

@observer
class List extends React.PureComponent<ViewProps> {
    page: observable = observable();
    locale: observable = observable();
    datagridStore: DatagridStore;
    @observable deleting = false;

    componentWillMount() {
        const router = this.props.router;
        const {
            route: {
                options: {
                    resourceKey,
                },
            },
        } = router;

        if (!resourceKey) {
            throw new Error('The route does not define the mandatory resourceKey option');
        }

        router.bindQuery('page', this.page, '1');
        router.bindQuery('locale', this.locale);

        this.datagridStore = new DatagridStore(resourceKey, {
            page: this.page,
            locale: this.locale,
        });
    }

    componentWillUnmount() {
        const {router} = this.props;
        this.datagridStore.destroy();
        router.unbindQuery('page');
        router.unbindQuery('locale');
    }

    handleEditClick = (rowId) => {
        const {router} = this.props;
        router.navigate(router.route.options.editRoute, {id: rowId}, {locale: this.locale.get()});
    };

    render() {
        const {
            route: {
                options: {
                    title,
                    editRoute,
                },
            },
        } = this.props.router;

        return (
            <div>
                {title && <h1>{translate(title)}</h1>}
                <Datagrid
                    store={this.datagridStore}
                    views={['table']}
                    onItemClick={editRoute && this.handleEditClick}
                />
            </div>
        );
    }
}

export default withToolbar(List, function() {
    const {
        route: {
            options: {
                resourceKey,
                locales,
            },
        },
    } = this.props.router;

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

    return {
        locale,
        items: [
            {
                type: 'button',
                value: translate('sulu_admin.add'),
                icon: 'plus-circle',
                onClick: () => {},
            },
            {
                type: 'button',
                value: translate('sulu_admin.delete'),
                icon: 'trash-o',
                disabled: this.datagridStore.selections.length === 0,
                loading: this.deleting,
                onClick: action(() => {
                    this.deleting = true;

                    const deletePromises = [];
                    this.datagridStore.selections.forEach((id) => {
                        deletePromises.push(ResourceRequester.delete(resourceKey, id));
                    });

                    return Promise.all(deletePromises).then(action(() => {
                        this.datagridStore.clearSelection();
                        this.datagridStore.sendRequest();
                        this.deleting = false;
                    }));
                }),
            },
        ],
    };
});
