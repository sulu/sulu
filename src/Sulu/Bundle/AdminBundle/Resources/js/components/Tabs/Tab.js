// @flow
import React from 'react';
import classNames from 'classnames';
import tabStyles from './tab.scss';

type Props = {
    children: string,
    index?: number,
    onClick?: (index: ?number) => void,
    selected: boolean,
    small: boolean,
};

export default class Tab extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
        small: false,
    };

    handleClick = () => {
        const {
            index,
            onClick,
        } = this.props;

        if (onClick) {
            onClick(index);
        }
    };

    render() {
        const {
            children,
            selected,
            small,
        } = this.props;

        const tabClass = classNames(
            tabStyles.tab,
            {
                [tabStyles.selected]: selected,
                [tabStyles.small]: small,
            }
        );

        return (
            <li className={tabClass}>
                <button
                    disabled={selected}
                    onClick={this.handleClick}
                    title={children}
                >
                    {children}
                </button>
            </li>
        );
    }
}
