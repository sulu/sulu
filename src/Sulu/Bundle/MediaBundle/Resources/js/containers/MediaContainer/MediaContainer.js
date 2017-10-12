// @flow
import React from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import {translate, ResourceRequester} from 'sulu-admin-bundle/services';
import {withToolbar, Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';

const COLLECTION_ROUTE = 'sulu_media.overview';
const COLLECTIONS_RESOURCE_KEY = 'collections';
const MEDIA_RESOURCE_KEY = 'media';

type Props = {
    page: observable,
    locale?: observable,
    views: Array<string>,
    collectionId: string | number,
    onOpenFolder: (collectionId: string | number) => void,
};

@observer
export default class MediaContainer extends React.PureComponent<Props> {
    handleCollectionClick = (collectionId: string | number) => {
        this.props.onOpenFolder(collectionId);
    };

    render() {
        return (
            <div>
                <Datagrid
                    store={this.collectionStore}
                    views={['folder']}
                    onItemClick={this.handleCollectionClick}
                />
            </div>
        );
    }
}
