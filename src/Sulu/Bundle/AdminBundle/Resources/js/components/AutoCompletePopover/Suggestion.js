// @flow
import React from 'react';
import Icon from '../Icon';
import suggestionStyles from './suggestion.scss';
import type {Node} from 'react';

type Props = {|
    children: string | (highlight: (text: string) => Node) => Node,
    icon?: string,
    minWidth: number,
    onSelect: (value: Object) => void,
    query: ?string,
    value: Object,
|};

export default class Suggestion extends React.PureComponent<Props> {
    static defaultProps = {
        minWidth: 0,
        query: '',
    };

    highlightMatchingTextPart = (text: string) => {
        if (!text) {
            return null;
        }

        if (!this.props.query) {
            return text;
        }

        let matcher;
        let splittedText;
        let highlightedWords = [];
        try {
            // try to match all highlighted parts using case insensitive regular expression
            matcher = new RegExp(this.props.query, 'gi');
            splittedText = text.split(matcher);
            highlightedWords = text.match(matcher);
        } catch (e) {
            // fallback to highlight first exact match if given query is an invalid regular expression like "*"
            splittedText = text.split(this.props.query);
            highlightedWords = [];
            for (let i = 0; i < splittedText.length - 1; i++) {
                highlightedWords.push(this.props.query);
            }
        }

        return (
            <span>
                {splittedText.map((splitText, index) => {
                    return (
                        <>
                            {splitText}
                            {highlightedWords && highlightedWords[index]
                                ? <strong>{highlightedWords[index]}</strong>
                                : null
                            }
                        </>
                    );
                })}
            </span>
        );
    };

    handleClick = () => {
        const {
            value,
            onSelect,
        } = this.props;

        if (onSelect) {
            onSelect(value);
        }
    };

    render() {
        const {
            minWidth,
            icon,
            children,
        } = this.props;

        return (
            <li
                className={suggestionStyles.suggestionItem}
                style={{minWidth: minWidth + 'px'}}
            >
                <button
                    className={suggestionStyles.suggestion}
                    onClick={this.handleClick}
                    type="button"
                >
                    {icon &&
                        <Icon
                            className={suggestionStyles.icon}
                            name={icon}
                        />
                    }
                    {typeof children === 'string' &&
                        this.highlightMatchingTextPart(children)
                    }
                    {typeof children === 'function' &&
                        children(this.highlightMatchingTextPart)
                    }
                </button>
            </li>
        );
    }
}
