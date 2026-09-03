// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import Table from '../../../../components/Table';
import AttributeGroupTable from '../AttributeGroupTable';

const headerCells = [
    <Table.HeaderCell key="label">Attribute</Table.HeaderCell>,
    <Table.HeaderCell key="required">Required</Table.HeaderCell>,
];

test('renders the header cells and the rows', () => {
    render(
        <AttributeGroupTable headerCells={headerCells}>
            <Table.Row id="a1" key="a1">
                <Table.Cell key="label">Size</Table.Cell>
                <Table.Cell key="required">yes</Table.Cell>
            </Table.Row>
        </AttributeGroupTable>
    );

    expect(screen.getByText('Attribute')).toBeInTheDocument();
    expect(screen.getByText('Required')).toBeInTheDocument();
    expect(screen.getByText('Size')).toBeInTheDocument();
    expect(screen.getByText('yes')).toBeInTheDocument();
});
