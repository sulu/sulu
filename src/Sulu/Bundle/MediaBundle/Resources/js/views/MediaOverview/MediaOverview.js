// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate, ResourceRequester} from 'sulu-admin-bundle/services';
import {withToolbar, Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';

const COLLECTION_ROUTE = 'sulu_media.overview';
const COLLECTIONS_RESSOURCE_KEY = 'collections';

@observer
class MediaOverview extends React.PureComponent<ViewProps> {
    @observable title: string;
    @observable parentId: ?string | number;
    @observable collectionStore: DatagridStore;
    collectionId: string | number;
    disposer: () => void;

    componentWillMount() {
        this.disposer = autorun(this.load);
    }

    componentWillUnmount() {
        this.disposer();
    }

    load = () => {
        const {router} = this.props;
        const {
            attributes: {
                id,
            },
        } = router;

        if (id) {
            this.loadCollectionInfo(id);
        }

        this.createCollectionStore(id);
    };

    loadCollectionInfo(collectionId) {
        return ResourceRequester.get(COLLECTIONS_RESSOURCE_KEY, collectionId, {depth: 1})
            .then(action((collectionInfo) => {
                const parentCollection = collectionInfo._embedded.parent;
                this.title = collectionInfo.title;
                this.parentId = (parentCollection) ? parentCollection.id : undefined;
            }));
    }

    @action createCollectionStore(collectionId) {
        const {router} = this.props;
        this.collectionId = collectionId;
        this.collectionStore = new DatagridStore(
            COLLECTIONS_RESSOURCE_KEY,
            (collectionId) ? {parent: collectionId} : undefined
        );

        router.bindQuery('page', this.collectionStore.page, '1');
    }

    handleOpenFolder = (collectionId) => {
        const {router} = this.props;
        router.navigate(COLLECTION_ROUTE, {id: collectionId});
    };

    render() {
        return (
            <div>
                {this.title && <h1>{translate(this.title)}</h1>}
                <Datagrid
                    store={this.collectionStore}
                    views={['folderList']}
                    onItemEditClick={this.handleOpenFolder}
                />
            </div>
        );
    }
}

export default withToolbar(MediaOverview, function() {
    const router = this.props.router;

    return {
        disableAll: this.collectionStore.loading,
        backButton: (this.parentId !== undefined)
            ? {
                onClick: () => {
                    router.navigate(COLLECTION_ROUTE, {id: this.parentId});
                },
            }
            : undefined,
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
