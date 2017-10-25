// @flow
import React from 'react';
import classNames from 'classnames';
import tabStyles from './tab.scss';

type Props = {
    children: string,
    index?: number,
    selected: boolean,
    onClick?: (index: ?number) => void,
};

export default class Tab extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
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
        } = this.props;
        const tabClass = classNames(
            tabStyles.tab,
            {
                [tabStyles.selected]: selected,
            }
        );

        return (
            <li className={tabClass}>
                <button
                    title={children}
                    onClick={this.handleClick}
                    disabled={selected}
                >
                    {children}
                </button>
            </li>
        );
    }
}
