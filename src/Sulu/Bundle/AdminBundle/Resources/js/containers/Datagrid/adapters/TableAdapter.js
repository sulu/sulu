// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Pagination from '../../../components/Pagination';
import Table from '../../../components/Table';
import PaginatedLoadingStrategy from '../loadingStrategies/PaginatedLoadingStrategy';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import datagridFieldTransformerRegistry from '../registries/DatagridFieldTransformerRegistry';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class TableAdapter extends AbstractAdapter {
    static LoadingStrategy = PaginatedLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-align-justify';

    static defaultProps = {
        data: [],
    };

    renderCells(item: Object, schema: Object) {
        const schemaKeys = Object.keys(schema);
        return schemaKeys.map((schemaKey) => {
            const transformer = datagridFieldTransformerRegistry.get(schema[schemaKey].type);
            const value = transformer.transform(item[schemaKey]);

            return (
                <Table.Cell key={item.id + schemaKey}>{value}</Table.Cell>
            );
        });
    }

    renderHeaderCells(schema: Object) {
        const schemaKeys = Object.keys(schema);

        return schemaKeys.map((schemaKey) => {
            const label = schema[schemaKey].label ? schema[schemaKey].label : schemaKey;

            return(
                <Table.HeaderCell key={schemaKey}>
                    {label}
                </Table.HeaderCell>
            );
        });
    }

    getSchema() {
        const {
            schema,
        } = this.props;

        const newSchema = {};

        for (const key of Object.keys(schema)) {
            if (schema[key].visibility === 'never' || schema[key].visibility === 'no') {
                continue;
            }

            newSchema[key] = schema[key];
        }

        return newSchema;
    }

    render() {
        const {
            data,
            loading,
            onItemClick,
            onAllSelectionChange,
            onItemSelectionChange,
            onPageChange,
            page,
            pageCount,
            selections,
        } = this.props;
        const schema = this.getSchema();
        const buttons = [];

        if (onItemClick) {
            buttons.push({
                icon: 'su-pen',
                onClick: (rowId) => onItemClick(rowId),
            });
        }

        return (
            <Pagination
                total={pageCount}
                current={page}
                loading={loading}
                onChange={onPageChange}
            >
                <Table
                    buttons={buttons}
                    selectMode={onItemSelectionChange ? 'multiple' : undefined}
                    onRowSelectionChange={onItemSelectionChange}
                    onAllSelectionChange={onAllSelectionChange}
                >
                    <Table.Header>
                        {this.renderHeaderCells(schema)}
                    </Table.Header>
                    <Table.Body>
                        {data.map((item) => (
                            <Table.Row key={item.id} id={item.id} selected={selections.includes(item.id)}>
                                {this.renderCells(item, schema)}
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Pagination>
        );
    }
}
