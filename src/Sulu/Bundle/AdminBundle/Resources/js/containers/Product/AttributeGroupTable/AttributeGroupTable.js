// @flow
import React from 'react';
import Table from '../../../components/Table';
import attributeGroupTableStyles from './attributeGroupTable.scss';
import type {Node} from 'react';

type Props = {|
    children: Node,
    headerCells: Array<Object>,
|};

/**
 * @experimental We can not yet give BC Promise for this new container in Sulu 3.1.
 */
export default class AttributeGroupTable extends React.Component<Props> {
    render() {
        const {children, headerCells} = this.props;

        return (
            <div className={attributeGroupTableStyles.table}>
                <Table skin="flat">
                    <Table.Header>
                        {headerCells}
                    </Table.Header>
                    <Table.Body>
                        {/* $FlowFixMe */}
                        {children}
                    </Table.Body>
                </Table>
            </div>
        );
    }
}
