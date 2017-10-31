// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
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
    @observable popOverOpen: boolean = false;
    @observable popOverAnchorElement: ?ElementRef<*>;

    handleOnOptionClick = (event: Event) => {
        this.popOverAnchorElement = event.currentTarget;
        this.popOverOpen = true;
    };

    handlePopOverClose = () => {
        this.popOverOpen = false;
    };

    renderMenuOptions = (dropdownOptionConfigs: Array<DropdownOptionConfig>) => {
        let options : Array<Element<typeof Select.Option>> = [];

        dropdownOptionConfigs.map((dropdownOptionConfig: DropdownOptionConfig, index: number) => {
            const key = `option-${index}`;
            const handleClick = () => {
                dropdownOptionConfig.onClick(this.props.index);
            };

            options.push(
                <Select.Option key={key} onClick={handleClick}>{dropdownOptionConfig.label}</Select.Option>
            );
        });

        return options;
    };

    render = () => {
        const {icon, options, skin} = this.props;

        const className = classNames(
            toolbarStyles.item,
            {
                [toolbarStyles.skinBlue]: skin === 'blue',
            }
        );

        return (
            <div onClick={this.handleOnOptionClick} className={className}>
                <Icon name={icon} />
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
                                {this.renderMenuOptions(options)}
                            </Menu>
                        )
                    }
                </Popover>
            </div>
        );
    };
}

