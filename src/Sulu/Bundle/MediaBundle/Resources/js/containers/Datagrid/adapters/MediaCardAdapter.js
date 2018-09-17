// @flow
import {observer} from 'mobx-react';
import React from 'react';
import {Masonry, InfiniteScroller} from 'sulu-admin-bundle/components';
import type {DatagridAdapterProps} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import MediaCard from '../../../components/MediaCard';

const THUMBNAIL_SIZE = 'sulu-240x';

type Props = DatagridAdapterProps & {
    icon: string,
    showCoverWhenSelected?: boolean,
};

@observer
export default class MediaCardAdapter extends React.Component<Props> {
    static formatFileSize(size: number) {
        const megaByteThreshold = 1000000;
        const kiloByteThreshold = 1000;

        if (size > 1000000) {
            return `${(size / megaByteThreshold).toFixed(2)} MB`;
        } else {
            return `${(size / kiloByteThreshold).toFixed(2)} KB`;
        }
    }

    getDownloadDropdownProps(item: Object) {
        const baseURL = window.location.origin;
        const {thumbnails} = item;
        const imageSizes = [];

        imageSizes.push({
            url: baseURL + item.url,
            label: translate('sulu_media.copy_masterfile_url'),
        });

        if (thumbnails) {
            imageSizes.push(...Object.keys(thumbnails).map((itemKey) => {
                return {
                    url: baseURL + item.thumbnails[itemKey],
                    label: itemKey,
                };
            }));
        }

        return {
            imageSizes,
            onDownload: this.handleDownload,
            downloadCopyText: translate('sulu_media.copy_url'),
            downloadUrl: baseURL + item.url,
            downloadText: translate('sulu_media.download_masterfile'),
        };
    }

    handleDownload = (downloadURL: string) => {
        window.location.href = downloadURL;
    };

    render() {
        const {
            data,
            icon,
            loading,
            onItemClick,
            onItemSelectionChange,
            onPageChange,
            page,
            pageCount,
            selections,
            showCoverWhenSelected,
        } = this.props;

        return (
            <InfiniteScroller
                currentPage={page}
                loading={loading}
                onPageChange={onPageChange}
                totalPages={pageCount}
            >
                <Masonry>
                    {data.map((item: Object) => {
                        const meta = `${item.mimeType} ${MediaCardAdapter.formatFileSize(item.size)}`;
                        const downloadDropdownProps = this.getDownloadDropdownProps(item);
                        const selected = selections.includes(item.id);
                        const thumbnail = item.thumbnails ? item.thumbnails[THUMBNAIL_SIZE] : null;

                        return (
                            // TODO: Don't access properties like "title" directly.
                            <MediaCard
                                {...downloadDropdownProps}
                                icon={icon}
                                id={item.id}
                                image={thumbnail}
                                key={item.id}
                                meta={meta}
                                mimeType={item.mimeType}
                                onClick={onItemClick}
                                onSelectionChange={onItemSelectionChange}
                                selected={selected}
                                showCover={showCoverWhenSelected && selected}
                                title={item.title}
                            />
                        );
                    })}
                </Masonry>
            </InfiniteScroller>
        );
    }
}
