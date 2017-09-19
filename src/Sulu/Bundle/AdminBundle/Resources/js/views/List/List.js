// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import ResourceRequester from '../../services/ResourceRequester';
import {translate} from '../../services/Translator';
import {withToolbar} from '../../containers/Toolbar';
import type {ViewProps} from '../../containers/ViewRenderer/types';

@observer
class List extends React.PureComponent<ViewProps> {
    datagridStore: DatagridStore;

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

        this.datagridStore = new DatagridStore(resourceKey);

        router.bindQuery('page', this.datagridStore.page, '1');
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
        this.props.router.unbindQuery('page');
    }

    handleEditClick = (rowId) => {
        const {router} = this.props;
        router.navigate(router.route.options.editRoute, {id: rowId});
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
                    onRowEditClick={editRoute && this.handleEditClick}
                />
            </div>
        );
    }
}

export default withToolbar(List, function() {
    return {
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
                onClick: () => {
                    const {
                        route: {
                            options: {
                                resourceKey,
                            },
                        },
                    } = this.props.router;

                    const deletePromises = [];

                    this.datagridStore.selections.forEach((id) => {
                        deletePromises.push(ResourceRequester.delete(resourceKey, id));
                    });

                    return Promise.all(deletePromises).then(() => {
                        this.datagridStore.sendRequest();
                    });
                },
            },
        ],
    };
});
