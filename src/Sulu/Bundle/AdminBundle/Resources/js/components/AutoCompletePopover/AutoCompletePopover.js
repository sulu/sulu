// @flow
import React from 'react';
import type {ElementRef} from 'react';
import Mousetrap from 'mousetrap';
import {computed, observable} from 'mobx';
import Menu from '../Menu';
import Popover from '../Popover';
import Suggestion from './Suggestion';
import autoCompletePopoverStyles from './autoCompletePopover.scss';

type Props = {
    anchorElement: ElementRef<*>,
    idProperty: string,
    minWidth: number,
    onSelect: (suggestion: Object) => void,
    open: boolean,
    query: ?string,
    searchProperties: Array<string>,
    suggestions: Array<Object>,
};

export default class AutoCompletePopover extends React.Component<Props> {
    static defaultProps = {
        idProperty: 'id',
        minWidth: 0,
    };

    @observable suggestionsRef: ElementRef<*>;

    @computed get buttons() {
        if (!this.suggestionsRef) {
            return [];
        }

        return Array.from(this.suggestionsRef.getElementsByTagName('button'));
    }

    @computed get activeButtonIndex() {
        return this.buttons.findIndex((button) => button === document.activeElement);
    }

    setSuggestionsRef = (suggestionsRef: ElementRef<*>) => {
        this.suggestionsRef = suggestionsRef;
    };

    handlePopoverClose = () => {
        const {onSelect, suggestions} = this.props;
        if (suggestions.length > 0) {
            onSelect(suggestions[0]);
        }
    };

    handleUp = () => {
        const previousButton = this.buttons[this.activeButtonIndex - 1];
        if (previousButton) {
            previousButton.focus();
        }
    };

    handleDown = () => {
        const nextButton = this.buttons[this.activeButtonIndex + 1];
        if (nextButton) {
            nextButton.focus();
        }
    };

    componentDidUpdate(prevProps: Props) {
        if (this.props.open === true && prevProps.open === false) {
            Mousetrap.bind('up', this.handleUp);
            Mousetrap.bind('down', this.handleDown);
        }

        if (this.props.open === false && prevProps.open === true) {
            Mousetrap.unbind('up');
            Mousetrap.unbind('down');
        }
    }

    render() {
        const {
            anchorElement,
            idProperty,
            minWidth,
            onSelect,
            open,
            query,
            searchProperties,
            suggestions,
        } = this.props;

        return (
            <Popover
                anchorElement={anchorElement}
                horizontalOffset={5}
                onClose={this.handlePopoverClose}
                open={open}
                popoverChildRef={this.setSuggestionsRef}
                verticalOffset={-2}
            >
                {
                    (setPopoverElementRef, popoverStyle) => (
                        <Menu
                            menuRef={setPopoverElementRef}
                            style={popoverStyle}
                        >
                            {suggestions.map((searchResult) => (
                                <Suggestion
                                    key={searchResult[idProperty]}
                                    minWidth={minWidth}
                                    onSelect={onSelect}
                                    query={query}
                                    value={searchResult}
                                >
                                    {(highlight) => searchProperties.map((field) => (
                                        <span className={autoCompletePopoverStyles.column} key={field}>
                                            {highlight(searchResult[field])}
                                        </span>
                                    ))}
                                </Suggestion>
                            ))}
                        </Menu>
                    )
                }
            </Popover>
        );
    }
}
