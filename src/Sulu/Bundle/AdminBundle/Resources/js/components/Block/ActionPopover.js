// @flow
import React from 'react';
import {Menu, Popover} from '..';
import Icon from '../Icon';
import actionPopoverStyles from './actionPopover.scss';
import type {ActionConfig} from './types';
import type {ElementRef} from 'react';

type Props = {
    actions: Array<ActionConfig>,
    anchorElement: ?ElementRef<'*'>,
    onClose: () => void,
    open: boolean,
};

export default class ActionPopover extends React.PureComponent<Props> {
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
                                <li key={index}>
                                    <button
                                        className={actionPopoverStyles.action}
                                        /* eslint-disable-next-line react/jsx-no-bind */
                                        onClick={() => {
                                            action.onClick();
                                            this.props.onClose();
                                        }}
                                    >
                                        <Icon
                                            className={actionPopoverStyles.icon}
                                            name={action.icon}
                                        />
                                        {action.label}
                                    </button>
                                </li>
                            );
                        })}
                    </Menu>
                )}
            </Popover>
        );
    }
}
