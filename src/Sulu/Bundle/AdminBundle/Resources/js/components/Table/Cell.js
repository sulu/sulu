// @flow
import React from 'react';
import classNames from 'classnames';
import tableStyles from './table.scss';
import type {Node} from 'react';
import type {Width} from './types';

type Props = {
    children?: Node,
    className?: string,
    colSpan?: number,
    depth?: number,
    width?: Width,
};

const DEPTH_PADDING = 25;

export default class Cell extends React.PureComponent<Props> {
    static defaultProps = {
        width: 'auto',
    };

    render() {
        const {
            colSpan,
            children,
            className,
            depth,
            width,
        } = this.props;
        const cellClass = classNames(
            className,
            tableStyles.cell,
            {
                [tableStyles[width]]: width !== 'auto',
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
