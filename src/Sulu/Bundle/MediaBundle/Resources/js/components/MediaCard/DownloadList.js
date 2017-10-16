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
    directDownload: {
        url: string,
        label: string,
    },
    onDirectDownload: (url: string) => void,
};

export default class DownloadList extends React.PureComponent<Props> {
    createItems(): DownloadItemsList {
        const {
            copyText,
            imageSizes,
            directDownload,
        } = this.props;
        const directDownloadItem = (
            <DownloadListItem
                key="downloadlist-direct-download-item"
                url={directDownload.url}
                onClick={this.handleItemDownload}
            >
                {directDownload.label}
            </DownloadListItem>
        );
        const divider = <Menu.Divider key="downloadlist-divider" />;
        const copyableItems = imageSizes.map((imageSize, index) => (
            <DownloadListItem
                key={index}
                url={imageSize.url}
                onCopy={this.handleItemCopy}
                copyText={copyText}
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

    handleItemDownload = (url: string) => {
        this.props.onDirectDownload(url);
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
