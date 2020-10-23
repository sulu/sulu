// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import debounce from 'debounce';
import {translate} from '../../utils/Translator';
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
    closeOnSelect: boolean,
    displayValue: string,
    isOptionSelected: (option: Element<Class<Option<T>>>) => boolean,
    onClose?: () => void,
    onSelect: (value: T) => void,
    selectedVisualization?: OptionSelectedVisualization,
|};

@observer
class Select<T> extends React.Component<Props<T>> {
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

    @observable buttonRefs: Map<number, ElementRef<'button'>> = new Map();

    @observable searchText: string = '';

    @observable focusIndex: ?number;

    @observable open: boolean;

    @action openOptionList = () => {
        this.open = true;
    };

    @action closeOptionList = () => {
        const {onClose} = this.props;

        if (onClose) {
            onClose();
        }

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

    setButtonRef = (index: number) => action((ref: ?ElementRef<'li'>) => {
        if (ref) {
            this.buttonRefs.set(index, ref);
        } else if (this.buttonRefs.has(index)) {
            this.buttonRefs.delete(index);
        }
    });

    @action clearSearchText = () => {
        this.searchText = '';
    };

    debouncedClearSearchText = debounce(this.clearSearchText, 500);

    @action appendSearchText = (searchText: string) => {
        this.searchText += searchText;

        const hit = Array.from(this.buttonRefs.entries()).find(([index, ref]) => {
            return ref.textContent.startsWith(this.searchText);
        });

        if (hit) {
            this.setFocusIndex(hit[0])
        }

        this.debouncedClearSearchText();
    };

    cloneOption(originalOption: Element<Class<Option<T>>>, index: number): Element<Class<Option<T>>> {
        const anchorWidth = this.displayValueRef ? this.displayValueRef.getBoundingClientRect().width : 0;

        return React.cloneElement(originalOption, {
            anchorWidth,
            onClick: this.handleOptionClick,
            selected: this.props.isOptionSelected(originalOption),
            selectedVisualization: this.props.selectedVisualization,
            setFocusIndex: this.handleSetFocusIndex(index),
            optionRef: this.setSelectedOptionRef,
            buttonRef: this.setButtonRef(index),
        });
    }

    cloneAction(originalAction: Element<typeof Action>) {
        return React.cloneElement(originalAction, {
            afterAction: this.closeOptionList,
        });
    }

    cloneChildren(): SelectChildren<T> {
        return React.Children.map(this.props.children, (child: any, index: number) => {
            if (!child) {
                return child;
            }

            switch (child.type) {
                case Option:
                    return this.cloneOption(child, index);
                case Action:
                    return this.cloneAction(child);
                default:
                    return child;
            }
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

    @action setFocusIndex = (focusIndex: number) => {
        if (focusIndex < 0 || focusIndex >= this.buttonRefs.size) {
            return;
        }

        this.focusIndex = focusIndex;

        if (this.buttonRefs.has(focusIndex)) {
            const ref = this.buttonRefs.get(focusIndex);
            ref.focus();
        }
    };

    handleSetFocusIndex = (focusIndex: number) => () => {
        this.setFocusIndex(focusIndex);
    };

    @action handleKeyDown = (event: KeyboardEvent) => {
        if (!this.open) {
            return;
        }

        if (event.key === 'Escape' || event.code === 27) {
            event.preventDefault();
            this.closeOptionList();

            return;
        }

        if (event.key === 'Tab' || event.code === 9) {
            event.preventDefault();
            this.clearSearchText();

            return;
        }

        if (event.key === 'ArrowUp' || event.code === 38) {
            event.preventDefault();
            this.setFocusIndex(this.focusIndex - 1);
            this.clearSearchText();

            return;
        }

        if (event.key === 'ArrowDown' || event.code === 40) {
            event.preventDefault();
            this.setFocusIndex(this.focusIndex + 1);
            this.clearSearchText();

            return;
        }

    };

    handleKeyPress = (event: KeyboardEvent) => {
        if (!this.open) {
            return;
        }

        event.preventDefault();
        this.appendSearchText(event.key);
    };

    render() {
        const {
            icon,
            disabled,
            displayValue,
            skin,
        } = this.props;
        const clonedChildren = this.cloneChildren();

        return (
            <div className={selectStyles.select} onKeyDown={this.handleKeyDown} onKeyPress={this.handleKeyPress}>
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
                                {React.Children.count(clonedChildren) > 0 ? clonedChildren : (
                                    <Option disabled={true} value={null}>
                                        {translate('sulu_admin.no_options_available')}
                                    </Option>
                                )}
                            </Menu>
                        )
                    }
                </Popover>
            </div>
        );
    }
}

export default Select;
