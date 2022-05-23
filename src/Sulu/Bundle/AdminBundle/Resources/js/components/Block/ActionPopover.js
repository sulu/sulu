// @flow
import React from 'react';
import {Menu, Popover} from '..';
import ActionPopoverItem from './ActionPopoverItem';
import type {ActionConfig} from './types';
import type {ElementRef} from 'react';

type Props = {
    actions: Array<ActionConfig>,
    anchorElement: ?ElementRef<'*'>,
    onClose: () => void,
    open: boolean,
};

export default class ActionPopover extends React.PureComponent<Props> {
    handleActionClick = (index: number) => {
        const {actions, onClose} = this.props;
        const action = actions[index];

        if (action.type === 'divider') {
            throw new Error('Divider actions cannot be clicked! This should not happen and is likely a bug.');
        }

        action.onClick();
        onClose();
    };

    render() {
        const {
            open,
            onClose,
            anchorElement,
        } = this.props;

        return (
            <Popover
                anchorElement={anchorElement}
                onClose={onClose}
                open={open}
                verticalOffset={5}
            >
                {(setPopoverRef, popoverStyle) => (
                    <Menu
                        menuRef={setPopoverRef}
                        style={popoverStyle}
                    >
                        {this.props.actions.map((action, index) => {
                            if (action.type === 'divider') {
                                return <Menu.Divider key={index} />;
                            }

                            return (
                                <ActionPopoverItem
                                    icon={action.icon}
                                    index={index}
                                    key={index}
                                    label={action.label}
                                    onClick={this.handleActionClick}
                                />
                            );
                        })}
                    </Menu>
                )}
            </Popover>
        );
    }
}
