// @flow
import React from 'react';
import type {ElementRef} from 'react';
import Menu from '../Menu';
import Popover from '../Popover';
import Suggestion from './Suggestion';
import autoCompletePopoverStyles from './autoCompletePopover.scss';

type Props = {
    anchorElement: ElementRef<*>,
    onSelect: (suggestion: Object) => void,
    open: boolean,
    query: ?string,
    searchProperties: Array<string>,
    suggestions: Array<Object>,
};

export default class AutoCompletePopover extends React.Component<Props> {
    handlePopoverClose = () => {
        const {onSelect, suggestions} = this.props;
        if (suggestions.length > 0) {
            onSelect(suggestions[0]);
        }
    };

    render() {
        const {
            anchorElement,
            onSelect,
            open,
            query,
            searchProperties,
            suggestions,
        } = this.props;

        return (
            <Popover
                open={open}
                onClose={this.handlePopoverClose}
                anchorElement={anchorElement}
                verticalOffset={-2}
                horizontalOffset={5}
            >
                {
                    (setPopoverElementRef, popoverStyle) => (
                        <Menu
                            style={popoverStyle}
                            menuRef={setPopoverElementRef}
                        >
                            {suggestions.map((searchResult) => (
                                <Suggestion
                                    key={searchResult.id}
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
