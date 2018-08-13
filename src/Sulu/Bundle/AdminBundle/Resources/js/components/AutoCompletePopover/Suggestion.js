// @flow
import React from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import suggestionStyles from './suggestion.scss';

type Props = {|
    value: Object,
    query: ?string,
    icon?: string,
    children: string | (highlight: (text: string) => Node) => Node,
    onSelect: (value: Object) => void,
|};

export default class Suggestion extends React.PureComponent<Props> {
    static defaultProps = {
        query: '',
    };

    highlightMatchingTextPart = (text: string) => {
        if (!text) {
            return;
        }

        const query = this.props.query || '';
        const regex = new RegExp(query, 'gi');
        const matches = text.match(regex);

        if (!matches || query.length === 0) {
            return text;
        }

        let matchIndex = 0;
        const highlightedMatches = text.replace(regex, () => {
            return `<strong>${matches[matchIndex++]}</strong>`;
        });

        return (
            <span dangerouslySetInnerHTML={{ __html: highlightedMatches }} />
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
            icon,
            children,
        } = this.props;

        return (
            <li
                className={suggestionStyles.suggestionItem}
            >
                <button
                    className={suggestionStyles.suggestion}
                    onClick={this.handleClick}
                >
                    {icon &&
                        <Icon
                            name={icon}
                            className={suggestionStyles.icon}
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
