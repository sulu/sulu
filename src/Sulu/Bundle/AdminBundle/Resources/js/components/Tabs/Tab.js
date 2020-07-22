// @flow
import React from 'react';
import type {ElementRef} from 'react';
import classNames from 'classnames';
import type {Skin} from './types';
import tabStyles from './tab.scss';

type Props = {
    children: string,
    index?: number,
    onClick?: (index: ?number) => void,
    selected: boolean,
    setRef?: (ref: ?ElementRef<'li'>) => void,
    skin: Skin,
};

export default class Tab extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
        skin: 'default',
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
            setRef,
            selected,
            skin,
        } = this.props;
        const tabClass = classNames(
            tabStyles.tab,
            {
                [tabStyles.compact]: skin === 'compact',
                [tabStyles.selected]: selected,
            }
        );

        return (
            <li className={tabClass} ref={setRef}>
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
