// @flow
import React from 'react';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import {Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';

type Props = {
    page: observable,
    locale: observable,
    datagridViews: Array<string>,
    collectionStore: DatagridStore,
    onCollectionOpen: (collectionId: string | number) => void,
};

@observer
export default class MediaContainer extends React.PureComponent<Props> {
    handleCollectionClick = (collectionId: string | number) => {
        this.props.onCollectionOpen(collectionId);
    };

    render() {
        const {
            collectionStore,
        } = this.props;

        return (
            <div>
                <Datagrid
                    store={collectionStore}
                    views={['folder']}
                    onItemClick={this.handleCollectionClick}
                />
            </div>
        );
    }
}
