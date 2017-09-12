// @flow
import React from 'react';
import Icon from '../Icon';
import suggestionStyles from './suggestion.scss';

type Props = {
    value: string,
    query: string,
    icon?: string,
    onSelection?: (value: string) => void,
};

export default class Suggestion extends React.PureComponent<Props> {
    static defaultProps = {
        query: '',
    };

    prepareValue(value: string) {
        const {query} = this.props;
        const regex = new RegExp(query, 'gi');
        const matches = value.match(regex);

        if (!matches || query.length === 0) {
            return value;
        }

        let matchIndex = 0;
        const highlightedMatches = value.replace(regex, () => {
            return `<strong>${matches[matchIndex++]}</strong>`;
        });

        return (
            <span dangerouslySetInnerHTML={{
                __html: highlightedMatches,
            }}
            />
        );
    }

    handleClick = () => {
        const {
            value,
            onSelection,
        } = this.props;

        if (onSelection) {
            onSelection(value);
        }
    };

    render() {
        const {
            icon,
            value,
        } = this.props;
        const prepardedValue = this.prepareValue(value);

        return (
            <button
                className={suggestionStyles.suggestion}
                onClick={this.handleClick}
            >
                {
                    icon &&
                    <Icon
                        name={icon}
                        className={suggestionStyles.icon}
                    />
                }
                <span>
                    {prepardedValue}
                </span>
            </button>
        );
    }
}
