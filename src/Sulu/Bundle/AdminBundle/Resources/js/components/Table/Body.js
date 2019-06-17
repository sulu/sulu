// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import type {ButtonConfig, SelectMode} from './types';
import Row from './Row';

type Props<T: number | string> = {
    /** @ignore */
    buttons?: Array<ButtonConfig>,
    children?: ChildrenArray<Element<typeof Row>>,
    /** @ignore */
    onRowCollapse?: (rowId: T) => void,
    /** @ignore */
    onRowExpand?: (rowId: T) => void,
    /** @ignore */
    onRowSelectionChange?: (rowId: T, selected?: boolean) => void,
    /** @ignore */
    selectInFirstCell: boolean,
    /** @ignore */
    selectMode?: SelectMode,
};

export default class Body<T: number | string> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        selectInFirstCell: false,
        selectMode: 'none',
    };

    cloneRows = (originalRows: ?ChildrenArray<Element<typeof Row>>) => {
        if (!originalRows) {
            return undefined;
        }

        const {buttons, selectMode} = this.props;
        return React.Children.map(originalRows, (row, index) => React.cloneElement(
            row,
            {
                buttons: buttons,
                ...row.props,
                key: `body-row-${index}`,
                rowIndex: index,
                selectMode: selectMode,
                selectInFirstCell: this.props.selectInFirstCell,
                onSelectionChange: this.props.onRowSelectionChange ? this.handleRowSelectionChange : undefined,
                onExpand: this.handleRowExpand,
                onCollapse: this.handleRowCollapse,
            }
        ));
    };

    handleRowSelectionChange = (rowId: T, selected?: boolean) => {
        const {onRowSelectionChange} = this.props;
        if (onRowSelectionChange) {
            onRowSelectionChange(rowId, selected);
        }
    };

    handleRowExpand = (rowId: T) => {
        const {onRowExpand} = this.props;
        if (onRowExpand) {
            onRowExpand(rowId);
        }
    };

    handleRowCollapse = (rowId: T) => {
        const {onRowCollapse} = this.props;
        if (onRowCollapse) {
            onRowCollapse(rowId);
        }
    };

    render() {
        const {children} = this.props;
        const rows = this.cloneRows(children);

        return (
            <tbody>
                {rows}
            </tbody>
        );
    }
}
