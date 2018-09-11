// @flow
import React from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import suggestionStyles from './suggestion.scss';

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
