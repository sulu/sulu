// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {translate} from 'sulu-admin-bundle/services';
import {withToolbar, Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';

const COLLECTIONS_RESSOURCE_KEY = 'collections';

@observer
class MediaList extends React.PureComponent<ViewProps> {
    collectionId: number;
    collectionStore: DatagridStore;

    componentWillMount() {
        const {router} = this.props;
        const {
            attributes: {
                id,
            },
        } = router;

        this.createCollectionStore(id);
    }

    componentWillUpdate(nextProps: ViewProps) {
        const {
            attributes: {
                id,
            },
        } = nextProps.router;

        if (id !== this.collectionId) {
            this.createCollectionStore(id);
        }
    }

    createCollectionStore(collectionId) {
        const {router} = this.props;
        this.collectionId = collectionId;
        this.collectionStore = new DatagridStore(COLLECTIONS_RESSOURCE_KEY, {parent: collectionId});

        router.bindQuery('page', this.collectionStore.page, '1');
    }

    handleOpenFolder = (collectionId) => {
        const {router} = this.props;
        router.navigate(router.route.options.collectionRoute, {id: collectionId});
    };

    render() {
        const {
            route: {
                options: {
                    title,
                },
            },
        } = this.props.router;

        return (
            <div>
                {title && <h1>{translate(title)}</h1>}
                <Datagrid
                    store={this.collectionStore}
                    views={['folderList']}
                    onItemEditClick={this.handleOpenFolder}
                />
            </div>
        );
    }
}

export default withToolbar(MediaList, function() {
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
                onClick: () => {},
            },
        ],
    };
});
