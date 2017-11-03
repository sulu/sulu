// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import {observable} from 'mobx';
import {observer} from 'mobx-react';
import Icon from '../Icon';
import Popover from '../Popover';
import Menu from '../Menu';
import Select from '../Select';
import type {DropdownOptionConfig, DropdownProps} from './types';
import toolbarStyles from './toolbar.scss';

@observer
export default class Dropdown extends React.Component<DropdownProps> {
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

    renderMenuOptions = (dropdownOptionConfigs: Array<DropdownOptionConfig>) => {
        return dropdownOptionConfigs.map((dropdownOptionConfig: DropdownOptionConfig, index: number) => {
            const key = `option-${index}`;
            const handleClick = dropdownOptionConfig.onClick;

            return (
                <Select.Option key={key} value={this.props.index} onClick={handleClick}>
                    {dropdownOptionConfig.label}
                </Select.Option>
            );
        });
    };

    render = () => {
        const {icon, options, skin} = this.props;

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
                            <Menu
                                style={popoverStyle}
                                menuRef={setPopoverElementRef}
                            >
                                {this.renderMenuOptions(options)}
                            </Menu>
                        )
                    }
                </Popover>
            </div>
        );
    };
}

