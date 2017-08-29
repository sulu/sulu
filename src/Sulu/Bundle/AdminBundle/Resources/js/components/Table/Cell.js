// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import tableStyles from './table.scss';

type Props = {
    children?: Node,
    className?: string,
    /**
     * @ignore 
     * If set to true, the cell appears as a control-cell 
     */
    isControl?: boolean,
    /** If set to true, the cell will not stretch and stay at minimal width */
    small: boolean,
    colspan?: number,
};

export default class Cell extends React.PureComponent<Props> {
    static defaultProps = {
        small: false,
        isControl: false,
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
            tableStyles.cell,
            {
                [tableStyles.small]: small,
            }
        );

        return (
            <td
                colSpan={colspan}
                className={cellClass}>
                {children}
            </td>
        );
    }
}
