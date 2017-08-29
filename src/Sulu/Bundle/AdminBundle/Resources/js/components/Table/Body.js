// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import Row from './Row';
import type {ButtonConfig, SelectMode} from './types';

type Props = {
    children?: ChildrenArray<Element<typeof Row>>,
    /** 
     * @ignore 
     * List of buttons to apply action handlers to every row (e.g. edit row) forwarded from table 
     */
    buttons?: Array<ButtonConfig>,
    /**
     * @ignore
     * Can be set to "single" or "multiple". Defaults is "none".
     */
    selectMode?: SelectMode,
    /** 
     * @ignore 
     * Callback function to notify about selection and deselection of a row
     */
    onRowSelectionChange?: (rowId: string | number, selected?: boolean) => void,
};

export default class Body extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
    };

    cloneRows = (originalRows: any) => {
        const {buttons, selectMode} = this.props;
        return React.Children.map(originalRows, (row, index) => {
            return React.cloneElement(
                row,
                {
                    ...row.props,
                    key: `body-row-${index}`,
                    rowIndex: index,
                    buttons: buttons,
                    selectMode: selectMode,
                    onSingleSelectionChange: this.handleRowSingleSelectionChange,
                    onMultipleSelectionChange: this.handleRowMultipleSelectionChange,
                },
            );
        });
    };

    handleRowSingleSelectionChange = (rowId: string | number) => {
        if (this.props.onRowSelectionChange) {
            this.props.onRowSelectionChange(rowId);
        }
    };

    handleRowMultipleSelectionChange = (checked: boolean, rowId: string | number) => {
        if (this.props.onRowSelectionChange) {
            this.props.onRowSelectionChange(rowId, checked);
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
