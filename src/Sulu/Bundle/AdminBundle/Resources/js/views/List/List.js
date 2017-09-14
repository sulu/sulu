// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import resourceMetadataStore from '../../stores/ResourceMetadataStore';
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

        this.datagridStore = new DatagridStore(
            resourceKey,
            resourceMetadataStore.getBaseUrl(resourceKey)
        );

        router.bindQuery('page', this.datagridStore.page, '1');
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
        this.props.router.unbindQuery('page');
    }

    handleEditClick = (rowId) => {
        const {router} = this.props;
        router.navigate(router.route.options.editLink, {uuid: rowId});
    };

    render() {
        return (
            <div>
                <h1>List</h1>
                <Datagrid
                    store={this.datagridStore}
                    onRowEditClick={this.handleEditClick}
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
                onClick: () => {},
            },
        ],
    };
});
