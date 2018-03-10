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
    selectInFirstCell?: boolean,
    /** @ignore */
    onRowSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    /** @ignore */
    onRowToggleChange?: (rowId: string | number, expanded?: boolean) => void,
};

export default class Body extends React.PureComponent<Props> {
    static defaultProps = {
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
                ...row.props,
                key: `body-row-${index}`,
                rowIndex: index,
                buttons: buttons,
                selectMode: selectMode,
                selectInFirstCell: this.props.selectInFirstCell,
                onSelectionChange: this.handleRowSelectionChange,
                onToggleChange: this.handleRowToggleChange,
            }
        ));
    };

    handleRowSelectionChange = (rowId: string | number, selected?: boolean) => {
        if (this.props.onRowSelectionChange) {
            this.props.onRowSelectionChange(rowId, selected);
        }
    };

    handleRowToggleChange = (rowId: string | number, expanded?: boolean) => {
        if (this.props.onRowToggleChange) {
            this.props.onRowToggleChange(rowId, expanded);
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
