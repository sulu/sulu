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
        try {
            // try to match all highlighted parts using case insensitive regular expression
            matcher = new RegExp(this.props.query, 'gi');
        } catch (e) {
            // fallback to highlight first exact match if given query is an invalid regular expression like "*"
            matcher = this.props.query;
        }

        const highlightedText = text.replace(matcher, '<strong>$&</strong>');

        return (
            <span dangerouslySetInnerHTML={{__html: highlightedText}} />
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
