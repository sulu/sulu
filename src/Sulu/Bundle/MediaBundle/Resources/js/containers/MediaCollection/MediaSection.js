//@flow
import React from 'react';
import {Datagrid, DatagridStore} from 'sulu-admin-bundle/containers';

type Props = {
    adapters: Array<string>,
    datagridStore: DatagridStore,
    onMediaClick: (mediaId: string | number) => void,
};

export default class MediaSection extends React.PureComponent<Props> {
    handleMediaClick = (mediaId: string | number) => {
        this.props.onMediaClick(mediaId);
    };

    render() {
        const {
            adapters,
            datagridStore,
        } = this.props;

        return (
            <Datagrid
                adapters={adapters}
                onItemClick={this.handleMediaClick}
                store={datagridStore}
            />
        );
    }
}
