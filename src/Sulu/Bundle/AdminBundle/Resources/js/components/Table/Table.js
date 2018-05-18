// @flow
import {observer} from 'mobx-react';
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Icon from '../Icon';
import Header from './Header';
import Body from './Body';
import Row from './Row';
import Cell from './Cell';
import HeaderCell from './HeaderCell';
import type {ButtonConfig, SelectMode} from './types';
import tableStyles from './table.scss';

const PLACEHOLDER_ICON = 'su-battery-low';

type Props = {
    buttons?: Array<ButtonConfig>,
    /** List of buttons to apply action handlers to every row (e.g. edit row) */
    children: ChildrenArray<Element<typeof Header | typeof Body>>,
    /** Can be set to "single" or "multiple". Defaults is "none". */
    onAllSelectionChange?: (checked: boolean) => void,
    onRowCollapse?: (rowId: string | number) => void,
    /**
     * Callback function to notify about selection and deselection of a row.
     * If the "id" prop is set on the row, the "rowId" corresponds to that, else it is the index of the row.
     */
    onRowExpand?: (rowId: string | number) => void,
    /**
     * Callback function to notify about open of a row.
     * If the "id" prop is set on the row, the "rowId" corresponds to that, else it is the index of the row.
     */
    onRowSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    /**
     * Callback function to notify about close of a row.
     * If the "id" prop is set on the row, the "rowId" corresponds to that, else it is the index of the row.
     */
    placeholderText?: string,
    /** Called when the "select all" checkbox in the header was clicked. Returns the checked state. */
    selectInFirstCell?: boolean,
    /** Text shown when the table has no entries */
    selectMode?: SelectMode,
};

@observer
export default class Table extends React.Component<Props> {
    static defaultProps = {
        selectMode: 'none',
    };

    static Header = Header;

    static Body = Body;

    static Row = Row;

    static Cell = Cell;

    static HeaderCell = HeaderCell;

    cloneHeader = (originalHeader?: Element<typeof Header>, allSelected: boolean) => {
        if (!originalHeader) {
            return null;
        }

        return React.cloneElement(
            originalHeader,
            {
                allSelected: allSelected,
                buttons: this.props.buttons,
                selectMode: this.props.selectMode,
                selectInFirstCell: this.props.selectInFirstCell,
                onAllSelectionChange: this.props.onAllSelectionChange ? this.handleAllSelectionChange : undefined,
            }
        );
    };

    cloneBody = (originalBody?: Element<typeof Body>) => {
        if (!originalBody) {
            return null;
        }

        return React.cloneElement(
            originalBody,
            {
                buttons: this.props.buttons,
                selectMode: this.props.selectMode,
                selectInFirstCell: this.props.selectInFirstCell,
                onRowSelectionChange: this.props.onRowSelectionChange ? this.handleRowSelectionChange : undefined,
                onRowExpand: this.handleRowExpand,
                onRowCollapse: this.handleRowCollapse,
            }
        );
    };

    checkAllRowsSelected = (body: Element<typeof Body>) => {
        const rows = body.props.children;

        if (!rows) {
            return false;
        }

        const rowSelections = React.Children.map(rows, (row) => row.props.selected);

        return !rowSelections.includes(false);
    };

    createTablePlaceholderArea = () => {
        const {placeholderText} = this.props;

        return (
            <div className={tableStyles.tablePlaceholderArea}>
                <Icon className={tableStyles.tablePlaceholderIcon} name={PLACEHOLDER_ICON} />
                {placeholderText &&
                    <div className={tableStyles.tablePlaceholderText}>
                        {placeholderText}
                    </div>
                }
            </div>
        );
    };

    handleRowExpand = (rowId: string | number) => {
        const {onRowExpand} = this.props;
        if (onRowExpand) {
            onRowExpand(rowId);
        }
    };

    handleRowCollapse = (rowId: string | number) => {
        const {onRowCollapse} = this.props;
        if (onRowCollapse) {
            onRowCollapse(rowId);
        }
    };

    handleAllSelectionChange = (checked: boolean) => {
        const {onAllSelectionChange} = this.props;
        if (onAllSelectionChange) {
            onAllSelectionChange(checked);
        }
    };

    handleRowSelectionChange = (rowId: string | number, selected?: boolean) => {
        const {onRowSelectionChange} = this.props;
        if (onRowSelectionChange) {
            onRowSelectionChange(rowId, selected);
        }
    };

    render() {
        const {children} = this.props;
        let body;
        let header;

        React.Children.forEach(children, (child: Element<typeof Header | typeof Body>) => {
            switch (child.type) {
                case Header:
                    header = child;
                    break;
                case Body:
                    body = child;
                    break;
                default:
                    throw new Error(
                        'The Table component only accepts the following children types: ' +
                        [Header.name, Body.name].join(', ')
                    );
            }
        });

        const clonedBody = this.cloneBody(body);
        const emptyBody = (clonedBody && React.Children.count(clonedBody.props.children) === 0);
        const allRowsSelected = (clonedBody && !emptyBody) ? this.checkAllRowsSelected(clonedBody) : false;
        const clonedHeader = this.cloneHeader(header, allRowsSelected);

        return (
            <div className={tableStyles.tableContainer}>
                <table className={tableStyles.table}>
                    {clonedHeader}
                    {clonedBody}
                </table>
                {emptyBody &&
                    this.createTablePlaceholderArea()
                }
            </div>
        );
    }
}
