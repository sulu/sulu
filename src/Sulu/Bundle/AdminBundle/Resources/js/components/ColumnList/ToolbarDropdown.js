// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import Icon from '../Icon';
import Popover from '../Popover';
import ToolbarDropdownList from './ToolbarDropdownList';
import type {ToolbarDropdown as ToolbarDropdownProps} from './types';
import toolbarStyles from './toolbar.scss';

@observer
export default class ToolbarDropdown extends React.Component<ToolbarDropdownProps> {
    static defaultProps = {
        skin: 'primary',
    };

    @observable popoverOpen: boolean = false;
    @observable popoverAnchorElement: ?ElementRef<*>;

    handleOptionClick = (event: SyntheticEvent<HTMLOptionElement>) => {
        this.popoverAnchorElement = event.currentTarget;
        this.popoverOpen = true;
    };

    handlePopoverClose = () => {
        this.popoverOpen = false;
    };

    render = () => {
        const {icon, options, skin, index} = this.props;

        const className = classNames(
            toolbarStyles.item,
            toolbarStyles[skin]
        );

        return (
            <div onClick={this.handleOptionClick} className={className}>
                <Icon name={icon} />
                <Popover
                    open={this.popoverOpen}
                    anchorElement={this.popoverAnchorElement}
                    onClose={this.handlePopoverClose}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <div
                                style={popoverStyle}
                                ref={setPopoverElementRef}
                            >
                                <ToolbarDropdownList
                                    index={index}
                                    style={popoverStyle}
                                    options={options}
                                />
                            </div>
                        )
                    }
                </Popover>
            </div>
        );
    };
}

