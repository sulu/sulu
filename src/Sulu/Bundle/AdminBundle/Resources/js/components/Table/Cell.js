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
    colspan?: number,
};

export default class Cell extends React.PureComponent<Props> {
    static defaultProps = {
        isControl: false,
    };

    render() {
        const {
            colspan,
            children,
            isControl,
        } = this.props;
        const cellClass = classNames(
            tableStyles.cell,
            {
                [tableStyles.controlCell]: isControl,
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
