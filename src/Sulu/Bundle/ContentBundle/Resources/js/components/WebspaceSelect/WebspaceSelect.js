// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Icon, ArrowMenu} from 'sulu-admin-bundle/components';
import webspaceSelectStyles from './webspaceSelect.scss';

type Props = {
    onChange?: (value: string) => void,
    value: ?string,
    children: ChildrenArray<Element<typeof ArrowMenu.Item>>;
};

@observer
export default class WebspaceSelect extends React.Component<Props> {
    static Item = ArrowMenu.Item;

    @observable open: boolean = false;

    @action openMenu = () => {
        this.open = true;
    };

    @action closeMenu = () => {
        this.open = false;
    };

    handleButtonClick = this.openMenu;

    handleMenuClose = this.closeMenu;

    handleChange = (value: string) => {
        const {onChange} = this.props;

        if (!onChange) {
            return;
        }

        this.closeMenu();
        onChange(value);
    };

    get displayValue(): string {
        let displayValue = 'Please choose your webspace';

        React.Children.forEach(this.props.children, (child: any) => {
            if (this.props.value === child.props.value) {
                displayValue = child.props.children;
            }
        });

        return displayValue;
    }

    renderButton() {
        return (
            <div className={webspaceSelectStyles.smartSelect}>
                <button
                    className={webspaceSelectStyles.button}
                    onClick={this.handleButtonClick}
                >
                    <Icon className={webspaceSelectStyles.buttonIcon} name="dot-circle-o" />
                    <span className={webspaceSelectStyles.buttonValue}>{this.displayValue}</span>
                    <Icon className={webspaceSelectStyles.buttonIcon} name="chevron-down" />
                </button>
            </div>
        );
    }

    render() {
        const {
            value,
            children,
        } = this.props;

        return (
            <ArrowMenu onClose={this.handleMenuClose} open={this.open} anchorElement={this.renderButton()}>
                <ArrowMenu.ItemSection
                    icon="dot-circle-o"
                    title="Webspaces"
                    value={value}
                    onChange={this.handleChange}
                >
                    {children}
                </ArrowMenu.ItemSection>
            </ArrowMenu>
        );
    }
}
