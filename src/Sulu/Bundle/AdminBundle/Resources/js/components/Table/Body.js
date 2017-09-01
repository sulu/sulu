// @flow
import type {ChildrenArray} from 'react';
import React from 'react';
import type {ButtonConfig, SelectMode} from './types';

type Props = {
    children?: ChildrenArray<*>,
    /** @ignore */
    buttons?: Array<ButtonConfig>,
    /** @ignore */
    selectMode?: SelectMode,
    /** @ignore */
    onRowSelectionChange?: (rowId: string | number, selected?: boolean) => void,
};

export default class Body extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
    };

    cloneRows = (originalRows: ChildrenArray<*>) => {
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
                    onSelectionChange: this.handleRowSelectionChange,
                },
            );
        });
    };

    handleRowSelectionChange = (rowId: string | number, selected?: boolean) => {
        if (this.props.onRowSelectionChange) {
            this.props.onRowSelectionChange(rowId, selected);
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
