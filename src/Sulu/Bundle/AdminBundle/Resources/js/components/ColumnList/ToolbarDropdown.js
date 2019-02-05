// @flow
import React, {Fragment} from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import {observable, action} from 'mobx';
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

    @action handleClick = (event: SyntheticEvent<HTMLOptionElement>) => {
        this.popoverAnchorElement = event.currentTarget;
        this.popoverOpen = true;
    };

    @action handlePopoverClose = () => {
        this.popoverOpen = false;
    };

    render() {
        const {icon, options, skin} = this.props;

        const className = classNames(
            toolbarStyles.item,
            toolbarStyles[skin]
        );

        return (
            <Fragment>
                <a className={className} onClick={this.handleClick}>
                    <Icon name={icon} />
                </a>
                <Popover
                    anchorElement={this.popoverAnchorElement}
                    onClose={this.handlePopoverClose}
                    open={this.popoverOpen}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <div
                                ref={setPopoverElementRef}
                                style={popoverStyle}
                            >
                                <ToolbarDropdownList
                                    onClick={this.handlePopoverClose}
                                    options={options}
                                />
                            </div>
                        )
                    }
                </Popover>
            </Fragment>
        );
    }
}
