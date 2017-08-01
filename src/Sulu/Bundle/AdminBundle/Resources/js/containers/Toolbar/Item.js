// @flow
import {action, observable} from 'mobx';
import React from 'react';
import classNames from 'classnames';
import {observer} from 'mobx-react';
import type {Item as ItemType} from './types';
import Icon from '../../components/Icon';
import itemStyles from './item.scss';
import Dropdown from './Dropdown';

export default class Item extends React.PureComponent<ItemType> {
    static defaultProps = {
        enabled: true,
    };

    @observable hasDropdownOpen = false;

    @action toggleDropdown = () => {
        this.hasDropdownOpen = !this.hasDropdownOpen;
    };

    @action closeDropdown = () => {
        if (this.hasSubItems) {
            this.hasDropdownOpen = false;
        }
    };

    componentWillMount() {
        const {items} = this.props;

        this.hasSubItems = items && items.length;
    }

    handleClick = () => {
        const {enabled, onClick} = this.props;

        if (enabled) {
            if (onClick) {
                onClick();
            }

            if (this.hasSubItems) {
                this.toggleDropdown();
            }
        }
    };

    onSubItemSelected = () => {
        this.closeDropdown();
    };

    render() {
        const { 
            icon, 
            items,
            title, 
            enabled, 
        } = this.props;
        const itemContainerClasses = classNames({
            [itemStyles.container]: true,
            [itemStyles.hasDropdownOpen]: this.hasDropdownOpen,
        });

        return (
            <div className={itemContainerClasses}>
                <button className={itemStyles.item} disabled={!enabled} onClick={this.handleClick}>
                    <Icon className={itemStyles.icon} name={icon} />
                    <span className={itemStyles.title}>{title}</span>
                    {
                        this.hasSubItems &&
                        <Icon className={itemStyles.dropdownIcon} name='chevron-down' />
                    }
                </button>
                {
                    this.hasSubItems && 
                    <Dropdown items={this.props.items} isOpen={this.hasDropdownOpen} onItemSelected={this.onSubItemSelected} />
                }
            </div>
        );
    }
}
