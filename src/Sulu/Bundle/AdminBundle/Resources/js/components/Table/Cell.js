// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import tableStyles from './table.scss';

type Props = {
    children?: Node,
    className?: string,
    colSpan?: number,
    depth?: number,
    /** If set to true, the cell will not stretch and stay at minimal width */
    small: boolean,
};

const DEPTH_PADDING = 25;

export default class Cell extends React.PureComponent<Props> {
    static defaultProps = {
        small: false,
    };

    render() {
        const {
            colSpan,
            children,
            className,
            depth,
            small,
        } = this.props;
        const cellClass = classNames(
            className,
            tableStyles.cell,
            {
                [tableStyles.small]: small,
            }
        );
        const style = {};

        if (depth) {
            style.paddingLeft = (depth * DEPTH_PADDING) + 'px';
        }

        return (
            <td
                className={cellClass}
                colSpan={colSpan}
            >
                <div className={tableStyles.cellContent} style={style}>
                    {children}
                </div>
            </td>
        );
    }
}
