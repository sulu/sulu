// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {Menu, Popover} from 'sulu-admin-bundle/components';

const HORIZONTAL_OFFSET = 20;
const VERTICAL_OFFSET = 10;

type Props = {
    open: boolean,
    onClose: () => void,
    buttonRef: ElementRef<'button'>,
    imageSizes: Array<{value: string | number, label: string}>,
};

export default class DownloadList extends React.PureComponent<Props> {
    handleClose = () => {
        this.props.onClose();
    };

    handleDownloadLinkCopied = (value: string | number) => {
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
                verticalOffset={VERTICAL_OFFSET}
                horizontalOffset={HORIZONTAL_OFFSET}
            >
                {(setPopoverRef, popoverStyle) => (
                    <Menu
                        style={popoverStyle}
                        menuRef={setPopoverRef}
                    >
                        <li>Hello</li>
                    </Menu>
                )}
            </Popover>
        );
    }
}
