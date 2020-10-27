// @flow
import React from 'react';
import type {Element, ElementRef} from 'react';
import {action, observable, computed} from 'mobx';
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

    @observable focusedElementIndex: number = -1;

    @observable open: boolean;

    @computed get buttonTexts() {
        return Array.from(this.buttonRefs.entries())
            .map<[number, string]>(([index, ref]) => [index, ref.textContent]);
    }

    @computed get selectedIndex() {
        let selectedIndex = -1;

        React.Children.forEach(this.props.children, (child: any, index: number) => {
            if (!child || child.type !== Option) {
                return;
            }

            if (this.props.isOptionSelected(child)) {
                selectedIndex = index;
            }
        });

        return selectedIndex;
    }

    @action openOptionList = () => {
        this.open = true;
        this.focusedElementIndex = this.selectedIndex;
    };

    @action closeOptionList = () => {
        const {onClose} = this.props;

        if (onClose) {
            onClose();
        }

        this.open = false;

        if (this.displayValueRef) {
            this.displayValueRef.focus();
        }
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

    setButtonRef = (index: number) => action((ref: ?ElementRef<'button'>) => {
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

        const hit = this.buttonTexts.find(([, text]) => text.toLowerCase().startsWith(this.searchText.toLowerCase()));
        if (hit) {
            this.requestFocus(hit[0]);
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
            requestFocus: this.handleRequestFocus(index),
            optionRef: this.setSelectedOptionRef,
            buttonRef: this.setButtonRef(index),
        });
    }

    cloneAction(originalAction: Element<typeof Action>, index: number) {
        return React.cloneElement(originalAction, {
            afterAction: this.closeOptionList,
            buttonRef: this.setButtonRef(index),
            requestFocus: this.handleRequestFocus(index),
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
                    return this.cloneAction(child, index);
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

    @computed get highestButtonIndex() {
        const max = Math.max(...Array.from(this.buttonRefs.keys()));
        return max;
    }

    @action requestFocus = (focusedElementIndex: number) => {
        if (focusedElementIndex < 0 || focusedElementIndex > this.highestButtonIndex) {
            return;
        }

        if (this.buttonRefs.has(focusedElementIndex)) {
            this.focusedElementIndex = focusedElementIndex;
            const ref = this.buttonRefs.get(focusedElementIndex);

            if (ref) {
                ref.focus();
            }
        }
    };

    handleRequestFocus = (focusedElementIndex: number) => () => {
        this.requestFocus(focusedElementIndex);
    };

    @action handleKeyDown = (event: KeyboardEvent) => {
        if (!this.open) {
            return;
        }

        event.preventDefault();

        if (event.key === 'Escape') {
            this.closeOptionList();

            return;
        }

        let focusedElementIndex = this.focusedElementIndex;

        if (event.key === 'ArrowUp') {
            do {
                --focusedElementIndex;
            } while (!this.buttonRefs.has(focusedElementIndex) && focusedElementIndex > 0);
        }

        if (event.key === 'ArrowDown') {
            do {
                ++focusedElementIndex;
            } while (!this.buttonRefs.has(focusedElementIndex) && focusedElementIndex <= this.highestButtonIndex);
        }

        if (focusedElementIndex !== this.focusedElementIndex) {
            this.requestFocus(focusedElementIndex);
        }

        this.clearSearchText();
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
            <div
                className={selectStyles.select}
                onKeyDown={this.handleKeyDown}
                onKeyPress={this.handleKeyPress}
                role="none"
            >
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
