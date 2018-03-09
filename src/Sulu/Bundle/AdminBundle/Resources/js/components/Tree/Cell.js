// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import treeStyles from './tree.scss';

type Props = {
    children?: Node,
    className?: string,
    /** If set to true, the cell will not stretch and stay at minimal width */
    small: boolean,
    colspan?: number,
};

export default class Cell extends React.PureComponent<Props> {
    static defaultProps = {
        small: false,
    };

    render() {
        const {
            small,
            colspan,
            children,
            className,
        } = this.props;
        const cellClass = classNames(
            className,
            treeStyles.cell,
            {
                [treeStyles.small]: small,
            }
        );

        return (
            <td
                colSpan={colspan}
                className={cellClass}
            >
                {children}
            </td>
        );
    }
}
