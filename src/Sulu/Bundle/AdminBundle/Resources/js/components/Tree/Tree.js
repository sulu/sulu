// @flow
import {observer} from 'mobx-react';
import React from 'react';
import type {ButtonConfig, SelectMode} from './types';
import Header from './Header';
import Body from './Body';
import Node from './Node';
import Element from './Element';
import Children from './Children';

const PLACEHOLDER_ICON = 'battery-quarter';

type Props = {
    children: ChildrenArray<Element<typeof Header | typeof Body>>,
    /** List of buttons to apply action handlers to every row (e.g. edit row) */
    buttons?: Array<ButtonConfig>,
    /** Can be set to "single" or "multiple". Defaults is "none". */
    selectMode?: SelectMode,
    /**
     * Callback function to notify about selection and deselection of a row.
     * If the "id" prop is set on the row, the "rowId" corresponds to that, else it is the index of the row.
     */
    onRowSelectionChange?: (rowId: string | number, selected?: boolean) => void,
    /** Called when the "select all" checkbox in the header was clicked. Returns the checked state. */
    onAllSelectionChange?: (checked: boolean) => void,
    /** Text shown when the table has no entries */
    placeholderText?: string,
};

@observer
export default class Tree extends React.PureComponent<Props> {
    static defaultProps = {
        selectMode: 'none',
    };

    static Header = Header;
    static Body = Body;
    static Node = Node;
    static Element = Element;
    static Children = Children;

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
                onAllSelectionChange: this.handleAllSelectionChange,
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
                onRowSelectionChange: this.handleRowSelectionChange,
            }
        );
    };

    checkAllRowsSelected = (list: Element<typeof Body | typeof Children>) => {
        const nodes : ChildrenArray<Element<typeof Node>> = list.props.children;

        for (let node of nodes) {
            if (!node.props.selected) {
                return false;
            }

            // Check children if has.
            if (node.props.children.length === 2) {
                let children : Children = node.props.children[1];
                if (!this.checkAllRowsSelected(children)) {
                    return false;
                }
            }
        }

        return true;
    };

    handleRowSelectionChange = (rowId: string | number, selected?: boolean) => {
        if (this.props.onRowSelectionChange) {
            this.props.onRowSelectionChange(rowId, selected);
        }
    };

    handleAllSelectionChange = (checked: boolean) => {
        if (this.props.onAllSelectionChange) {
            this.props.onAllSelectionChange(checked);
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
                        'The Tree component only accepts the following children types: ' +
                        [Header.name, Body.name].join(', ')
                    );
            }
        });

        const clonedBody = this.cloneBody(body);
        const emptyBody = (clonedBody && React.Children.count(clonedBody.props.children) === 0);
        const allRowsSelected = (clonedBody && !emptyBody) ? this.checkAllRowsSelected(clonedBody) : false;
        const clonedHeader = this.cloneHeader(header, allRowsSelected);

        return (
            <div>
                {clonedHeader}
                {clonedBody}
            </div>
        );
    }
}
