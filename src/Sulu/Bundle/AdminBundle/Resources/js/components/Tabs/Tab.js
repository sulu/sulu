// @flow
import React from 'react';
import classNames from 'classnames';
import tabStyles from './tab.scss';
import type {Element, ElementRef} from 'react';
import type {Type} from './types';

type Props = {
    badges: Element<*>[],
    children: string,
    hidden: boolean,
    index?: number,
    onClick?: (index: ?number) => void,
    selected: boolean,
    tabRef?: (index: ?number, ref: ?ElementRef<'li'>) => void,
    type?: Type,
};

class Tab extends React.PureComponent<Props> {
    static defaultProps = {
        badges: [],
        hidden: false,
        selected: false,
    };

    setTabRef = (ref: ?ElementRef<'li'>) => {
        const {index, tabRef} = this.props;

        if (tabRef) {
            tabRef(index, ref);
        }
    };

    handleClick = () => {
        const {index, onClick} = this.props;

        if (onClick) {
            onClick(index);
        }
    };

    render() {
        const {
            badges,
            children,
            hidden,
            type,
            selected,
        } = this.props;

        const tabClass = classNames(
            tabStyles.tab,
            tabStyles[type],
            {
                [tabStyles.hidden]: hidden,
                [tabStyles.selected]: selected,
            }
        );

        return (
            <li className={tabClass} ref={this.setTabRef}>
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

export default Tab;
