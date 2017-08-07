// @flow
import {action, observable} from 'mobx';
import Action from './Action';
import Divider from './Divider';
import Dropdown from './Dropdown';
import Icon from '../Icon';
import Option from './Option';
import React from 'react';
import type {SelectData} from './types';
import {observer} from 'mobx-react';
import selectStyles from './select.scss';

@observer
export default class Select extends React.PureComponent {
    props: {
        value?: string,
        onChange?: (string) => void,
        children: Array<Option | Action | Divider>,
    };

    static defaultProps = {
        children: [],
    };

    @observable data: SelectData;
    @observable isOpen: boolean;
    button: HTMLElement;

    componentWillMount() {
        this.setInitialData();
    }

    setInitialData() {
        React.Children.forEach(this.props.children, (child) => {
            if (!this.data || (this.props.value && this.props.value === child.props.value)) {
                this.setData({
                    value: child.props.value,
                    label: child.props.children,
                });
            }
        });
    }

    @action setData = (data: SelectData) => {
        if (this.data && this.props.onChange) {
            this.props.onChange(data.value);
        }
        this.data = data;
    };

    @action openDropdown = () => {this.isOpen = true;};
    @action closeDropdown = () => {this.isOpen = false;};

    setButton = (b: HTMLElement) => this.button = b;
    handleButtonClick = this.openDropdown;
    handleDropDownRequestClose = this.closeDropdown;
    handleOptionClick = (data: SelectData) => {
        this.setData(data);
        this.closeDropdown();
    };

    render() {
        const dropdownChildren = this.renderDropdownChildren();
        let centeredChildIndex = dropdownChildren.findIndex((c) => c.props.selected);
        centeredChildIndex = centeredChildIndex < 0 ? 0 : centeredChildIndex;
        return (
            <div>
                <button
                    ref={this.setButton}
                    onClick={this.handleButtonClick}
                    className={selectStyles.button}>
                    {this.data.label}
                    <Icon className={selectStyles.icon} name="chevron-down" />
                </button>
                <Dropdown
                    labelTop={this.button ? this.button.offsetTop : 0}
                    labelLeft={this.button ? this.button.offsetLeft : 0}
                    isOpen={this.isOpen}
                    centeredChildIndex={centeredChildIndex}
                    onRequestClose={this.handleDropDownRequestClose}>
                    {dropdownChildren}
                </Dropdown>
            </div>
        );
    }

    renderDropdownChildren() {
        return React.Children.map(this.props.children, (child) => {
            if (child.type === Option) {
                child = React.cloneElement(child, {
                    onClick: this.handleOptionClick,
                    selected: child.props.value === this.data.value && !child.props.disabled,
                });
            }
            if (child.type === Action) {
                child = React.cloneElement(child, {
                    afterAction: this.closeDropdown,
                });
            }
            return child;
        });
    }
}
