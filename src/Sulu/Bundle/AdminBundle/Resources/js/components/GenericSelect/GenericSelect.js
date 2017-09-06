// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Popover from '../Popover';
import Action from './Action';
import Option from './Option';
import type {OptionSelectedVisualization, SelectChildren, SelectProps} from './types';
import DisplayValue from './DisplayValue';
import genericSelectStyles from './genericSelect.scss';

type Props = SelectProps & {
    onSelect: (values: string) => void,
    displayValue: string,
    closeOnSelect: boolean,
    isOptionSelected: (option: Element<typeof Option>) => boolean,
    selectedVisualization?: OptionSelectedVisualization,
};

@observer
export default class GenericSelect extends React.PureComponent<Props> {
    static defaultProps = {
        closeOnSelect: true,
    };

    @observable displayValueNode: ?ElementRef<'button'>;

    @observable selectedOptionNode: ?ElementRef<'li'>;

    @observable open: boolean;

    @action openPopup = () => {
        this.open = true;
    };

    @action closePopup = () => {
        this.open = false;
    };

    @action setDisplayValueNode = (node: ?ElementRef<'button'>) => {
        if (node) {
            this.displayValueNode = node;
        }
    };

    @action setSelectedOptionNode = (node: ?ElementRef<'li'>, selected: boolean) => {
        if (!this.selectedOptionNode || (node && selected)) {
            this.selectedOptionNode = node;
        }
    };

    cloneOption(originalOption: Element<typeof Option>) {
        return React.cloneElement(originalOption, {
            onClick: this.handleOptionClick,
            selected: this.props.isOptionSelected(originalOption),
            selectedVisualization: this.props.selectedVisualization,
            optionRef: this.setSelectedOptionNode,
        });
    }

    cloneAction(originalAction: Element<typeof Action>) {
        return React.cloneElement(originalAction, {
            afterAction: this.closePopup,
        });
    }

    cloneChildren(): SelectChildren {
        return React.Children.map(this.props.children, (child: any) => {
            switch (child.type) {
                case Option:
                    child = this.cloneOption(child);
                    break;
                case Action:
                    child = this.cloneAction(child);
                    break;
            }

            return child;
        });
    }

    handleOptionClick = (value: string) => {
        this.props.onSelect(value);

        if (this.props.closeOnSelect) {
            this.closePopup();
        }
    };

    handleDisplayValueClick = this.openPopup;

    handlePopoverClose = this.closePopup;

    render() {
        const {
            icon,
            displayValue,
        } = this.props;
        const clonedChildren = this.cloneChildren();

        return (
            <div className={genericSelectStyles.select}>
                <DisplayValue
                    displayValueRef={this.setDisplayValueNode}
                    icon={icon}
                    onClick={this.handleDisplayValueClick}
                >
                    {displayValue}
                </DisplayValue>
                <Popover
                    open={this.open}
                    anchorEl={this.displayValueNode}
                    centerChildNode={this.selectedOptionNode}
                    horizontalOffset={-20}
                    verticalOffset={2}
                    onClose={this.handlePopoverClose}
                >
                    <ul className={genericSelectStyles.optionsList}>
                        {clonedChildren}
                    </ul>
                </Popover>
            </div>
        );
    }
}
