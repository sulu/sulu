// @flow
import React from 'react';
import type {ChildrenArray, ElementRef} from 'react';
import {observer} from 'mobx-react';
import {action, computed, observable} from 'mobx';
import Input from '../Input';
import Popover from '../Popover';
import Menu from '../Menu';
import autoCompleteStyles from './autoComplete.scss';

const POPOVER_HORIZONTAL_OFFSET = 5;
const POPOVER_VERTICAL_OFFSET = -2;

type Props = {
    icon?: string,
    value: string,
    children: ChildrenArray<*>,
    threshold: number,
    placeholder?: string,
    onChange: (value: string) => void,
    noSuggestionsMessage?: string,
};

@observer
export default class AutoComplete extends React.PureComponent<Props> {
    static defaultProps = {
        value: '',
        threshold: 0,
    };

    @observable open: boolean = false;

    @observable inputRef: ElementRef<*>;

    previousSuggestionListChildrenCount: number = 0;

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

    createSuggestions(children: ChildrenArray<*>) {
        const {value} = this.props;

        return React.Children.map(children, (child, index: number) => {
            return (
                <li style={this.suggestionStyle}>
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

    hasReachedThreshold(value: string) {
        return value && value.length >= this.props.threshold;
    }

    setInputRef = (inputRef: ElementRef<'label'>) => {
        if (inputRef) {
            this.inputRef = inputRef;
        }
    };

    resizeSuggestionListOnContentChange = (suggestionListContent: ChildrenArray<*>, resizeList?: () => void) => {
        const currentSuggestionListChildrenCount = React.Children.count(suggestionListContent);

        if (resizeList && this.previousSuggestionListChildrenCount !== currentSuggestionListChildrenCount) {
            resizeList();
        }

        this.previousSuggestionListChildrenCount = currentSuggestionListChildrenCount;

        return suggestionListContent;
    };

    handleSuggestionSelection = (value: string) => {
        this.props.onChange(value);
        this.closeSuggestions();
    };

    handleChange = (value: string) => {
        if (this.hasReachedThreshold(value)) {
            this.openSuggestions();
        } else {
            this.closeSuggestions();
        }

        this.props.onChange(value);
    };

    handlePopoverClose = () => {
        this.closeSuggestions();
    };

    render() {
        const {
            icon,
            value,
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
                    icon={icon}
                    value={value}
                    inputRef={this.setInputRef}
                    onChange={this.handleChange}
                    placeholder={placeholder}
                />
                <Popover
                    open={this.open}
                    onClose={this.handlePopoverClose}
                    anchorElement={this.inputRef}
                    verticalOffset={POPOVER_VERTICAL_OFFSET}
                    horizontalOffset={POPOVER_HORIZONTAL_OFFSET}
                >
                    {
                        (setPopoverElementRef, popoverStyle, triggerResize) => (
                            <Menu
                                style={popoverStyle}
                                menuRef={setPopoverElementRef}
                            >
                                {
                                    this.resizeSuggestionListOnContentChange(
                                        suggestionListContent,
                                        triggerResize,
                                    )
                                }
                            </Menu>
                        )
                    }
                </Popover>
            </div>
        );
    }
}
