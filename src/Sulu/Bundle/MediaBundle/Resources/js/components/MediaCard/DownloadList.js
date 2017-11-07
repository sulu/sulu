// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {Menu, Popover} from 'sulu-admin-bundle/components';
import type {DownloadItemsList} from './types';
import DownloadListItem from './DownloadListItem';

type Props = {
    open: boolean,
    onClose: () => void,
    copyText: string,
    buttonRef: ElementRef<'button'>,
    imageSizes: Array<{url: string, label: string}>,
    downloadUrl: string,
    downloadText: string,
    onDownload: (url: string) => void,
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
                url={downloadUrl}
                onClick={this.handleItemDownload}
            >
                {downloadText}
            </DownloadListItem>
        );
        const divider = <Menu.Divider key="downloadlist-divider" />;
        const copyableItems = imageSizes.map((imageSize, index) => (
            <DownloadListItem
                key={index}
                url={imageSize.url}
                onClick={this.handleItemCopy}
                copyText={copyText}
                copyUrlOnClick={true}
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
                open={open}
                onClose={this.handleClose}
                anchorElement={buttonRef}
            >
                {(setPopoverRef, popoverStyle) => (
                    <Menu
                        style={popoverStyle}
                        menuRef={setPopoverRef}
                    >
                        {items}
                    </Menu>
                )}
            </Popover>
        );
    }
}
