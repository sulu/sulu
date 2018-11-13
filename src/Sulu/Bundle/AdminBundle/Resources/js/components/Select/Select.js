// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import Popover from '../Popover';
import Menu from '../Menu';
import Action from './Action';
import Option from './Option';
import type {OptionSelectedVisualization, SelectChildren, SelectProps} from './types';
import DisplayValue from './DisplayValue';
import selectStyles from './select.scss';

const HORIZONTAL_OFFSET = -20;
const VERTICAL_OFFSET = 2;

type Props<T> = {|
    ...SelectProps<T>,
    onSelect: (value: T) => void,
    displayValue: string,
    closeOnSelect: boolean,
    isOptionSelected: (option: Element<typeof Option>) => boolean,
    selectedVisualization?: OptionSelectedVisualization,
|};

@observer
export default class Select<T> extends React.Component<Props<T>> {
    static defaultProps = {
        closeOnSelect: true,
        disabled: false,
        skin: 'default',
    };

    static Action = Action;

    static Option = Option;

    static Divider = Menu.Divider;

    @observable displayValueRef: ?ElementRef<'button'>;

    @observable selectedOptionRef: ?ElementRef<'li'>;

    @observable open: boolean;

    @action openOptionList = () => {
        this.open = true;
    };

    @action closeOptionList = () => {
        this.open = false;
    };

    @action setDisplayValueRef = (ref: ?ElementRef<'button'>) => {
        if (ref) {
            this.displayValueRef = ref;
        }
    };

    @action setSelectedOptionRef = (ref: ?ElementRef<'li'>, selected: boolean) => {
        if (!this.selectedOptionRef || (ref && selected)) {
            this.selectedOptionRef = ref;
        }
    };

    cloneOption(originalOption: Element<typeof Option>) {
        const anchorWidth = this.displayValueRef ? this.displayValueRef.getBoundingClientRect().width : 0;

        return React.cloneElement(originalOption, {
            anchorWidth,
            onClick: this.handleOptionClick,
            selected: this.props.isOptionSelected(originalOption),
            selectedVisualization: this.props.selectedVisualization,
            optionRef: this.setSelectedOptionRef,
        });
    }

    cloneAction(originalAction: Element<typeof Action>) {
        return React.cloneElement(originalAction, {
            afterAction: this.closeOptionList,
        });
    }

    cloneChildren(): SelectChildren<T> {
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

    handleOptionClick = (value: T) => {
        this.props.onSelect(value);

        if (this.props.closeOnSelect) {
            this.closeOptionList();
        }
    };

    handleDisplayValueClick = this.openOptionList;

    handleOptionListClose = this.closeOptionList;

    render() {
        const {
            icon,
            disabled,
            displayValue,
            skin,
        } = this.props;
        const clonedChildren = this.cloneChildren();

        return (
            <div className={selectStyles.select}>
                <DisplayValue
                    disabled={disabled}
                    displayValueRef={this.setDisplayValueRef}
                    icon={icon}
                    onClick={this.handleDisplayValueClick}
                    skin={skin}
                >
                    {displayValue}
                </DisplayValue>
                <Popover
                    anchorElement={this.displayValueRef}
                    centerChildElement={this.selectedOptionRef}
                    horizontalOffset={HORIZONTAL_OFFSET}
                    onClose={this.handleOptionListClose}
                    open={this.open}
                    verticalOffset={VERTICAL_OFFSET}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <Menu
                                menuRef={setPopoverElementRef}
                                style={popoverStyle}
                            >
                                {clonedChildren}
                            </Menu>
                        )
                    }
                </Popover>
            </div>
        );
    }
}
