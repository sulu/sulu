// @flow
import React from 'react';
import classNames from 'classnames';
import Header from './Header';
import Body from './Body';
import type {SelectMode, SelectedRows, TableChildren} from './types';
import tableStyles from './table.scss';

export default class Table extends React.PureComponent {
    props: {
        /** Child nodes of the table */
        children: TableChildren,
        /** List of buttons to apply action handlers to every row (e.g. edit row) */
        controls?: Array<any>,
        /** CSS classes to apply custom styles */
        className?: string,
        /** Can be set to "single" or "multiple". Defaults is "none". */
        selectMode?: SelectMode,
        /** Callback function to notify about the selected row(s) */
        onRowSelection?: (rowId: SelectedRows) => void,
        /** Called when the "select all" checkbox in the header was clicked. Returns the checked state. */
        onSelectAll?: (checked: boolean) => void,
    };

    static defaultProps = {
        selectMode: 'none',
    };

    getTableComponents = (children: TableChildren) => {
        let body;
        let header;

        React.Children.forEach(children, (child) => {
            const {name} = child.type;

            switch (name) {
            case Header.name:
                header = this.cloneHeader(child);
                break;
            case Body.name:
                body = this.cloneBody(child);
                break;
            default:
                throw new Error(
                    'The Table component only accepts the following children types: ' +
                    [Header.name, Body.name].join(', ')
                );
            }
        });

        return {body, header};
    };

    cloneHeader = (originalHeader: Element<Header>) => {
        return React.cloneElement(
            originalHeader,
            {
                onSelectAll: this.onSelectAll,
                selectMode: this.props.selectMode,
            }
        );
    };

    cloneBody = (originalBody: Element<Body>) => {
        return React.cloneElement(
            originalBody,
            {
                controls: this.props.controls,
                onRowSelection: this.onRowSelection,
                selectMode: this.props.selectMode,
            }
        );
    };

    onSelectAll = (checked: boolean) => {
        if (this.props.onSelectAll) {
            this.props.onSelectAll(checked);
        }
    };

    onRowSelection = (rowIds: SelectedRows) => {
        if (this.props.onRowSelection) {
            this.props.onRowSelection(rowIds);
        }
    };

    render() {
        const {
            children,
            className,
        } = this.props;
        const {body, header} = this.getTableComponents(children);
        const tableClass = classNames(
            tableStyles.tableContainer,
            className,
        );

        return (
            <div className={tableClass}>
                <table className={tableStyles.table}>
                    {header}
                    {body}
                </table>
            </div>
        );
    }
}
