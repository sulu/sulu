// @flow
import React from 'react';
import type {ElementRef} from 'react';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import Icon from '../Icon';
import Popover from '../Popover';
import Menu from '../Menu';
import MultiSelect from '../MultiSelect';
import toolbarStyles from './toolbar.scss';

type Props = {
    onAddClick: () => void,
    onSearchClick: () => void,
    active: boolean,
};

@observer
export default class Toolbar extends React.PureComponent<Props> {
    @observable popOverOpen: boolean = false;
    @observable popOverAnchorElement: ?ElementRef<*>;

    handleOnOptionClick = (event: Event) => {
        this.popOverAnchorElement = event.currentTarget;
        this.popOverOpen = true;
    };

    handlePopOverClose = () => {
        this.popOverOpen = false;
    };

    renderMenuOptions = () => {
        return (
            <MultiSelect.Option>1</MultiSelect.Option>
        );
    };

    render() {
        const {onAddClick, onSearchClick, active} = this.props;

        const containerClass = classNames(
            toolbarStyles.container,
            {
                [toolbarStyles.isActive]: active,
            }
        );

        return (
            <div className={containerClass}>
                <span onClick={onAddClick} className={toolbarStyles.item}>
                    <Icon name="plus" />
                </span>
                <span onClick={onSearchClick} className={toolbarStyles.item}>
                    <Icon name="search" />
                </span>
                <span onClick={this.handleOnOptionClick} className={toolbarStyles.item}>
                    <Icon name="gear" />
                </span>
                <Popover
                    open={this.popOverOpen}
                    anchorElement={this.popOverAnchorElement}
                    onClose={this.handlePopOverClose}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <Menu
                                style={popoverStyle}
                                menuRef={setPopoverElementRef}
                            >
                                {this.renderMenuOptions()}
                            </Menu>
                        )
                    }
                </Popover>
            </div>
        );
    }
}

