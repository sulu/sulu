// @flow
import React from 'react';
import type {ChildrenArray, ElementRef} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import Input from '../Input';
import Popover from '../Popover';
import autoCompleteStyles from './autoComplete.scss';

const POPOVER_HORIZONTAL_OFFSET = 5;
const POPOVER_VERTICAL_OFFSET = -2;

type Props = {
    children: ChildrenArray<*>,
    onChange: (value: string) => void,
    value: string,
    threshold: number,
    inputIcon?: string,
    placeholder?: string,
    noSuggestionsMessage?: string,
};

@observer
export default class AutoComplete extends React.PureComponent<Props> {
    static defaultProps = {
        value: '',
        threshold: 0,
    };

    @observable open: boolean = false;

    inputRef: ElementRef<'label'>;

    @action openSuggestions() {
        this.open = true;
    }

    @action closeSuggestions() {
        this.open = false;
    }

    componentWillReceiveProps(nextProps: Props) {
        const {value} = nextProps;

        if (this.hasReachedThreshold(value)) {
            this.openSuggestions();
        } else if (this.open) {
            this.closeSuggestions();
        }
    }

    createSuggestions(children: ChildrenArray<*>) {
        const {value} = this.props;

        return React.Children.map(children, (child, index: number) => {
            return (
                <li>
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

    createNoSuggestionsMessage() {
        const {noSuggestionsMessage} = this.props;

        if (!noSuggestionsMessage) {
            return null;
        }

        return (
            <li className={autoCompleteStyles.noSuggestions}>
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

    handleSuggestionSelection = (value: string) => {
        this.props.onChange(value);
        this.closeSuggestions();
    };

    handleChange = (value: string) => {
        this.props.onChange(value);
    };

    handleInputFocus = () => {
        const {value} = this.props;

        if (this.hasReachedThreshold(value)) {
            this.openSuggestions();
        }
    };

    handlePopoverClose = () => {
        this.closeSuggestions();
    };

    render() {
        const {
            value,
            children,
            inputIcon,
            placeholder,
        } = this.props;
        const suggestions = this.createSuggestions(children);
        const noSuggestions = !React.Children.count(suggestions);
        const noSuggestionsMessage = this.createNoSuggestionsMessage();
        const suggestionListMinWidth = (this.inputRef) ? this.inputRef.scrollWidth - POPOVER_HORIZONTAL_OFFSET * 2: 0;
        const suggestionListStyle = {
            minWidth: suggestionListMinWidth,
        };

        return (
            <div className={autoCompleteStyles.autoComplete}>
                <Input
                    inputRef={this.setInputRef}
                    onFocus={this.handleInputFocus}
                    icon={inputIcon}
                    value={value}
                    placeholder={placeholder}
                    onChange={this.handleChange} />
                <Popover
                    open={this.open}
                    anchorEl={this.inputRef}
                    onClose={this.handlePopoverClose}
                    horizontalOffset={POPOVER_HORIZONTAL_OFFSET}
                    verticalOffset={POPOVER_VERTICAL_OFFSET}>
                    <ul
                        className={autoCompleteStyles.suggestions}
                        style={suggestionListStyle}>
                        {suggestions}
                        {noSuggestions &&
                            noSuggestionsMessage
                        }
                    </ul>
                </Popover>
            </div>
        );
    }
}
