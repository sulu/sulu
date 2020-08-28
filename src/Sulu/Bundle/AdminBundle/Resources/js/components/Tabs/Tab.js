// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import tabStyles from './tab.scss';

type Props = {
    children: string,
    hidden: boolean,
    index?: number,
    onClick?: (index: ?number) => void,
    selected: boolean,
    small: boolean,
    tabRef?: (index: ?number, ref: ?ElementRef<'li'>) => void,
};

class Tab extends React.PureComponent<Props> {
    static defaultProps = {
        hidden: false,
        selected: false,
        small: false,
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
            children,
            hidden,
            small,
            selected,
        } = this.props;

        const tabClass = classNames(
            tabStyles.tab,
            {
                [tabStyles.hidden]: hidden,
                [tabStyles.selected]: selected,
                [tabStyles.small]: small,
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
                </button>
            </li>
        );
    }
}

export default Tab;
