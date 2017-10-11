// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {Masonry} from 'sulu-admin-bundle/components';
import type {DatagridAdapterProps} from 'sulu-admin-bundle/containers';
import MediaCard from '../../../components/MediaCard';

const THUMBNAIL_SIZE = 'sulu-260x';

@observer
export default class MediaCardAdapter extends React.Component<DatagridAdapterProps> {
    static formatFileSize(size: number) {
        const megaByteThreshold = 1000000;
        const kiloByteThreshold = 1000;

        if (size > 1000000) {
            return `${(size / megaByteThreshold).toFixed(2)} MB`;
        } else {
            return `${(size / kiloByteThreshold).toFixed(2)} KB`;
        }
    }

    render() {
        const {
            data,
            onItemClick,
        } = this.props;

        return (
            <Masonry>
                {data.map((item: Object) => {
                    const meta = `${item.mimeType} ${MediaCardAdapter.formatFileSize(item.size)}`;

                    return (
                        // TODO: Don't access properties like "title" directly.
                        <MediaCard
                            key={item.id}
                            id={item.id}
                            title={item.title}
                            meta={meta}
                            image={item.thumbnails[THUMBNAIL_SIZE]}
                        />
                    );
                })}
            </Masonry>
        );
    }
}
