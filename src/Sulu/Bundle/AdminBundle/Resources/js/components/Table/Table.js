// @flow
import React from 'react';
import type {Element} from 'react';
import Header from './Header';
import Body from './Body';
import type {ControlItems, SelectMode, TableChildren} from './types';
import tableStyles from './table.scss';

type Props = {
    /** Child nodes of the table */
    children: TableChildren,
    /** List of buttons to apply action handlers to every row (e.g. edit row) */
    controls?: ControlItems,
    /** Can be set to "single" or "multiple". Defaults is "none". */
    selectMode?: SelectMode,
    /** 
     * Callback function to notify about selection and deselection of a row.
     * If the "id" prop is set on the row, the "rowId" corresponds to that, else it is the index of the row.
     */
    onRowSelectionChange?: (rowId: string | number, selected: boolean) => void,
    /** Called when the "select all" checkbox in the header was clicked. Returns the checked state. */
    onSelectAllChange?: (checked: boolean) => void,
    /** If true the "select all" checkbox is checked. */
    selectAllChecked?: boolean,
};

export default class Table extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
        selectAllChecked: false,
    };

    getTableComponents = (children: TableChildren) => {
        let body;
        let header;

        React.Children.forEach(children, (child: TableChildren) => {
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

    cloneHeader = (originalHeader: Element<typeof Header>) => {
        return React.cloneElement(
            originalHeader,
            {
                controls: this.props.controls,
                selectMode: this.props.selectMode,
                selectAllChecked: this.props.selectAllChecked,
                onSelectAllChange: this.onSelectAllChange,
            }
        );
    };

    cloneBody = (originalBody: Element<typeof Body>) => {
        return React.cloneElement(
            originalBody,
            {
                controls: this.props.controls,
                selectMode: this.props.selectMode,
                onRowSelectionChange: this.onRowSelectionChange,
            }
        );
    };

    onSelectAllChange = (checked: boolean) => {
        if (this.props.onSelectAllChange) {
            this.props.onSelectAllChange(checked);
        }
    };

    onRowSelectionChange = (rowId: string | number, selected: boolean) => {
        if (this.props.onRowSelectionChange) {
            this.props.onRowSelectionChange(rowId, selected);
        }
    };

    render() {
        const {
            children,
        } = this.props;
        const {body, header} = this.getTableComponents(children);

        return (
            <table className={tableStyles.table}>
                {header}
                {body}
            </table>
        );
    }
}
