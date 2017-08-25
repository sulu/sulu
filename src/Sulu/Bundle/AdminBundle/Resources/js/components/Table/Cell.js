// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import tableStyles from './table.scss';

type Props = {
    children?: Node,
    /**
     * @ignore 
     * If set to true, the cell appears as a control-cell 
     */
    isControl?: boolean,
    /** If set to true, the cell will not stretch and stay at minimal width */
    isSmall: boolean,
    colspan?: number,
};

export default class Cell extends React.PureComponent<Props> {
    static defaultProps = {
        isSmall: false,
        isControl: false,
    };

    render() {
        const {
            isSmall,
            colspan,
            children,
            isControl,
        } = this.props;
        const cellClass = classNames(
            tableStyles.cell,
            {
                [tableStyles.controlCell]: isControl,
                [tableStyles.small]: isSmall,
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
