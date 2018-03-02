// @flow
import React from 'react';
import type {ChildrenArray, Element, ElementRef} from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import debounce from 'debounce';
import Input from '../Input';
import Popover from '../Popover';
import Menu from '../Menu';
import Suggestion from './Suggestion';
import autoCompleteStyles from './autoComplete.scss';

const LENS_ICON = 'su-search';
const DEBOUNCE_TIME = 300;
const POPOVER_HORIZONTAL_OFFSET = 5;
const POPOVER_VERTICAL_OFFSET = -2;

type Props = {
    children: ChildrenArray<Element<typeof Suggestion>>,
    /** The value of the selected "Suggestion" */
    value: string,
    placeholder?: string,
    /** Shows the loading indicator when true */
    loading?: boolean,
    /** Called when a suggestion is set */
    onChange: (value: string | number) => void,
    onFinish: () => void,
    /** Called with a debounce when text is entered inside the input */
    onSearch: (query: string) => void,
};

@observer
export default class AutoComplete extends React.Component<Props> {
    static defaultProps = {
        value: '',
    };

    static Suggestion = Suggestion;

    @observable inputRef: ElementRef<'label'>;

    @observable inputValue: ?string = this.props.value;

    overrideValue: boolean = false;

    componentWillReceiveProps(nextProps: Props) {
        if (this.overrideValue) {
            this.overrideValue = false;
            this.setInputValue(nextProps.value);
        }
    }

    componentWillUnmount() {
        this.debouncedSearch.clear();
    }

    @computed get suggestionStyle(): Object {
        const suggestionListMinWidth = (this.inputRef) ? this.inputRef.scrollWidth - POPOVER_HORIZONTAL_OFFSET * 2 : 0;

        return {
            minWidth: Math.max(suggestionListMinWidth, 0),
        };
    }

    createSuggestions(children: ChildrenArray<Element<typeof Suggestion>>) {
        return React.Children.map(children, (child, index: number) => {
            return (
                <li
                    style={this.suggestionStyle}
                    className={autoCompleteStyles.suggestionItem}
                >
                    {
                        React.cloneElement(child, {
                            key: index,
                            query: this.inputValue,
                            onSelection: this.handleSuggestionSelection,
                        })
                    }
                </li>
            );
        });
    }

    @action setInputValue(value: ?string) {
        this.inputValue = value;
    }

    setInputRef = (inputRef: ?ElementRef<'label'>) => {
        if (inputRef) {
            this.inputRef = inputRef;
        }
    };

    debouncedSearch = debounce((query: string) => {
        this.props.onSearch(query);
    }, DEBOUNCE_TIME);

    handleSuggestionSelection = (value: string | number) => {
        this.overrideValue = true;
        this.props.onChange(value);
    };

    handleInputChange = (value: ?string) => {
        this.setInputValue(value);
        this.debouncedSearch(this.inputValue);
    };

    handlePopoverClose = () => {
        const {children} = this.props;
        const firstSuggestion = React.Children.toArray(children)[0];

        if (firstSuggestion && firstSuggestion.props) {
            this.overrideValue = true;
            this.props.onChange(firstSuggestion.props.value);
        }
    };

    render() {
        const {
            loading,
            children,
            onFinish,
            placeholder,
        } = this.props;
        const {inputValue} = this;
        const suggestions = this.createSuggestions(children);
        const showSuggestionList = (!!inputValue && inputValue.length > 0) && React.Children.count(children) > 0;

        return (
            <div className={autoCompleteStyles.autoComplete}>
                <Input
                    icon={LENS_ICON}
                    value={inputValue}
                    loading={loading}
                    inputRef={this.setInputRef}
                    onChange={this.handleInputChange}
                    onFinish={onFinish}
                    placeholder={placeholder}
                />
                <Popover
                    open={showSuggestionList}
                    onClose={this.handlePopoverClose}
                    anchorElement={this.inputRef}
                    verticalOffset={POPOVER_VERTICAL_OFFSET}
                    horizontalOffset={POPOVER_HORIZONTAL_OFFSET}
                >
                    {
                        (setPopoverElementRef, popoverStyle) => (
                            <Menu
                                style={popoverStyle}
                                menuRef={setPopoverElementRef}
                            >
                                {suggestions}
                            </Menu>
                        )
                    }
                </Popover>
            </div>
        );
    }
}
