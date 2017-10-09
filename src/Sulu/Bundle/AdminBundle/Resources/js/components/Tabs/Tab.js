// @flow
import React from 'react';
import type {ChildrenArray} from 'react';
import classNames from 'classnames';
import tabStyles from './tab.scss';

type Props = {
    children: ChildrenArray<*>,
    value: string | number,
    label: string,
    selected: boolean,
    onClick?: (value: string | number) => void,
};

export default class Tab extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
    };

    handleClick = () => {
        const {
            value,
            onClick,
        } = this.props;

        if (onClick) {
            onClick(value);
        }
    };

    render() {
        const {
            label,
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
                    title={label}
                    onClick={this.handleClick}
                    disabled={selected}
                >
                    {label}
                </button>
            </li>
        );
    }
}
