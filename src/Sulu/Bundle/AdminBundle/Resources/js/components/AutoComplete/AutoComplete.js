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

const LENS_ICON = 'search';
const DEBOUNCE_TIME = 250;
const POPOVER_HORIZONTAL_OFFSET = 5;
const POPOVER_VERTICAL_OFFSET = -2;

type Props = {
    children: ChildrenArray<Element<typeof Suggestion>>,
    /** The value inside the input field */
    value: string,
    placeholder?: string,
    /** Shows the loading indicator when true */
    loading?: boolean,
    /** Called on every input change */
    onChange: (value: string) => void,
    /** Debounced version of `onChange`. Put network requests inside of this handler */
    onDebouncedChange: (value: string) => void,
    onSuggestionSelection: (value: string) => void,
    /** Message when no `Suggestions` could be found */
    noSuggestionsMessage?: string,
};

@observer
export default class AutoComplete extends React.PureComponent<Props> {
    static defaultProps = {
        value: '',
        threshold: 0,
    };

    static Suggestion = Suggestion;

    @observable open: boolean = false;

    @observable inputRef: ElementRef<*>;

    previousSuggestionListChildrenCount: number = 0;

    componentWillUnmount() {
        this.debouncedUpdateSuggestions.clear();
    }

    @action openSuggestions() {
        this.open = true;
    }

    @action closeSuggestions() {
        this.open = false;
    }

    @computed get suggestionStyle(): Object {
        const suggestionListMinWidth = (this.inputRef) ? this.inputRef.scrollWidth - POPOVER_HORIZONTAL_OFFSET * 2: 0;

        return {
            minWidth: suggestionListMinWidth,
        };
    }

    createSuggestions(children: ChildrenArray<Element<typeof Suggestion>>) {
        const {value} = this.props;

        return React.Children.map(children, (child, index: number) => {
            return (
                <li
                    style={this.suggestionStyle}
                    className={autoCompleteStyles.suggestionItem}
                >
                    {
                        React.cloneElement(child, {
                            key: index,
                            query: value,
                            onSelection: this.handleSuggestionSelection,
                        })
                    }
                </li>
            );
        });
    }

    createNoSuggestionsMessage(): ElementRef<'li'> {
        const {noSuggestionsMessage} = this.props;

        if (!noSuggestionsMessage) {
            return null;
        }

        return (
            <li
                style={this.suggestionStyle}
                className={autoCompleteStyles.noSuggestions}
            >
                {noSuggestionsMessage}
            </li>
        );
    }

    setInputRef = (inputRef: ElementRef<'label'>) => {
        if (inputRef) {
            this.inputRef = inputRef;
        }
    };

    debouncedUpdateSuggestions = debounce((value: string) => {
        this.props.onDebouncedChange(value);
    }, DEBOUNCE_TIME);

    handleSuggestionSelection = (value: string) => {
        this.props.onSuggestionSelection(value);
        this.closeSuggestions();
    };

    handleChange = (value: string) => {
        if (value !== '') {
            this.openSuggestions();
        } else {
            this.closeSuggestions();
        }

        this.debouncedUpdateSuggestions(value);
        this.props.onChange(value);
    };

    handlePopoverClose = () => {
        this.closeSuggestions();
    };

    render() {
        const {
            value,
            loading,
            children,
            placeholder,
        } = this.props;
        const suggestions = this.createSuggestions(children);
        const noSuggestions = !React.Children.count(suggestions);
        const noSuggestionsMessage = this.createNoSuggestionsMessage();
        const suggestionListContent = noSuggestions ? noSuggestionsMessage : suggestions;

        return (
            <div className={autoCompleteStyles.autoComplete}>
                <Input
                    icon={LENS_ICON}
                    value={value}
                    loading={loading}
                    inputRef={this.setInputRef}
                    onChange={this.handleChange}
                    placeholder={placeholder}
                />
                {!loading &&
                    <Popover
                        open={this.open}
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
                                    {suggestionListContent}
                                </Menu>
                            )
                        }
                    </Popover>
                }
            </div>
        );
    }
}
