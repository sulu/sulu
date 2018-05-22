// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {Menu, Popover} from 'sulu-admin-bundle/components';
import type {DownloadItemsList} from './types';
import DownloadListItem from './DownloadListItem';

type Props = {
    buttonRef: ?ElementRef<'button'>,
    copyText: string,
    downloadText: string,
    downloadUrl: string,
    imageSizes: Array<{label: string, url: string}>,
    onClose: () => void,
    onDownload: (url: string) => void,
    open: boolean,
};

export default class DownloadList extends React.PureComponent<Props> {
    createItems(): DownloadItemsList {
        const {
            copyText,
            imageSizes,
            downloadUrl,
            downloadText,
        } = this.props;
        const directDownloadItem = (
            <DownloadListItem
                key="downloadlist-direct-download-item"
                onClick={this.handleItemDownload}
                url={downloadUrl}
            >
                {downloadText}
            </DownloadListItem>
        );
        const divider = <Menu.Divider key="downloadlist-divider" />;
        const copyableItems = imageSizes.map((imageSize, index) => (
            <DownloadListItem
                copyText={copyText}
                copyUrlOnClick={true}
                key={index}
                onClick={this.handleItemCopy}
                url={imageSize.url}
            >
                {imageSize.label}
            </DownloadListItem>
        ));

        return [
            directDownloadItem,
            divider,
            copyableItems,
        ];
    }

    handleClose = () => {
        this.props.onClose();
    };

    handleItemDownload = (url?: string) => {
        if (url) {
            this.props.onDownload(url);
        }
    };

    handleItemCopy = () => {
        this.props.onClose();
    };

    render() {
        const {
            open,
            buttonRef,
        } = this.props;
        const items = this.createItems();

        return (
            <Popover
                anchorElement={buttonRef}
                onClose={this.handleClose}
                open={open}
            >
                {(setPopoverRef, popoverStyle) => (
                    <Menu
                        menuRef={setPopoverRef}
                        style={popoverStyle}
                    >
                        {items}
                    </Menu>
                )}
            </Popover>
        );
    }
}
