// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {Menu, Popover} from 'sulu-admin-bundle/components';
import DownloadListItem from './DownloadListItem';

type Props = {
    open: boolean,
    onClose: () => void,
    buttonRef: ElementRef<'button'>,
    imageSizes: Array<{url: string, label: string}>,
    copyInfo: string,
};

export default class DownloadList extends React.PureComponent<Props> {
    handleClose = () => {
        this.props.onClose();
    };

    handleDownloadItemCopy = () => {
        this.props.onClose();
    };

    render() {
        const {
            open,
            copyInfo,
            buttonRef,
            imageSizes,
        } = this.props;

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
                        {imageSizes.map((imageSize, index) => (
                            <DownloadListItem
                                key={index}
                                url={imageSize.url}
                                onCopy={this.handleDownloadItemCopy}
                                copyInfo={copyInfo}
                            >
                                {imageSize.label}
                            </DownloadListItem>
                        ))}
                    </Menu>
                )}
            </Popover>
        );
    }
}
