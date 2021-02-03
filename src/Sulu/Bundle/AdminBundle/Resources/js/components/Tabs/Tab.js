// @flow
import React from 'react';
import type {Element} from 'react';
import classNames from 'classnames';
import tabStyles from './tab.scss';

type Props = {
    badges: Element<*>[],
    children: string,
    index?: number,
    onClick?: (index: ?number) => void,
    selected: boolean,
};

export default class Tab extends React.PureComponent<Props> {
    static defaultProps = {
        badges: [],
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
            badges,
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
                    disabled={selected}
                    onClick={this.handleClick}
                    title={children}
                >
                    {children}
                    {!!badges && !!badges.length &&
                        <div className={tabStyles.badges}>
                            {badges}
                        </div>
                    }
                </button>
            </li>
        );
    }
}
