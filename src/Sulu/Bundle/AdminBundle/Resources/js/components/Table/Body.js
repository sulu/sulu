// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import type {ButtonConfig, SelectMode} from './types';
import Row from './Row';

type Props = {
    children?: ChildrenArray<Element<typeof Row>>,
    /** @ignore */
    buttons?: Array<ButtonConfig>,
    /** @ignore */
    selectMode?: SelectMode,
    /** @ignore */
    selectInFirstCell: boolean,
    /** @ignore */
    onRowSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    /** @ignore */
    onRowExpand?: (rowId: string | number) => void,
    /** @ignore */
    onRowCollapse?: (rowId: string | number) => void,
};

export default class Body extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
        selectInFirstCell: false,
    };

    cloneRows = (originalRows: ?ChildrenArray<Element<typeof Row>>) => {
        if (!originalRows) {
            return undefined;
        }

        const {buttons, selectMode} = this.props;
        return React.Children.map(originalRows, (row, index) => React.cloneElement(
            row,
            {
                ...row.props,
                key: `body-row-${index}`,
                rowIndex: index,
                buttons: buttons,
                selectMode: selectMode,
                selectInFirstCell: this.props.selectInFirstCell,
                onSelectionChange: this.handleRowSelectionChange,
                onExpand: this.handleRowExpand,
                onCollapse: this.handleRowCollapse,
            }
        ));
    };

    handleRowSelectionChange = (rowId: string | number, selected?: boolean) => {
        if (this.props.onRowSelectionChange) {
            this.props.onRowSelectionChange(rowId, selected);
        }
    };

    handleRowExpand = (rowId: string | number) => {
        if (this.props.onRowExpand) {
            this.props.onRowExpand(rowId);
        }
    };

    handleRowCollapse = (rowId: string | number) => {
        if (this.props.onRowCollapse) {
            this.props.onRowCollapse(rowId);
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
