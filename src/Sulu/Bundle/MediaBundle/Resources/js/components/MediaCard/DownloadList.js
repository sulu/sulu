// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {Menu, Popover} from 'sulu-admin-bundle/components';

type Props = {
    open: boolean,
    onClose: () => void,
    buttonRef: ElementRef<'button'>,
    imageSizes: Array<{url: string, label: string}>,
};

export default class DownloadList extends React.PureComponent<Props> {
    handleClose = () => {
        this.props.onClose();
    };

    handleDownloadLinkCopied = () => {
        this.props.onClose();
    };

    render() {
        const {
            open,
            buttonRef,
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
                        <li>Hallo</li>
                    </Menu>
                )}
            </Popover>
        );
    }
}
