// @flow
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import React from 'react';
import Pagination from '../../../components/Pagination';
import Table from '../../../components/Table';
import PaginatedLoadingStrategy from '../loadingStrategies/PaginatedLoadingStrategy';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import datagridFieldTransformerRegistry from '../registries/DatagridFieldTransformerRegistry';
import type {Schema} from '../types';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class TableAdapter extends AbstractAdapter {
    static LoadingStrategy = PaginatedLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-align-justify';

    static defaultProps = {
        data: [],
    };

    @computed get schema(): Schema {
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

    renderCells(item: Object) {
        const schemaKeys = Object.keys(this.schema);

        return schemaKeys.map((schemaKey) => {
            const transformer = datagridFieldTransformerRegistry.get(this.schema[schemaKey].type);
            const value = transformer.transform(item[schemaKey]);

            return (
                <Table.Cell key={item.id + schemaKey}>{value}</Table.Cell>
            );
        });
    }

    renderHeaderCells() {
        const schemaKeys = Object.keys(this.schema);

        return schemaKeys.map((schemaKey) => {
            const label = this.schema[schemaKey].label ? this.schema[schemaKey].label : schemaKey;

            return(
                <Table.HeaderCell key={schemaKey}>
                    {label}
                </Table.HeaderCell>
            );
        });
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
                        {this.renderHeaderCells()}
                    </Table.Header>
                    <Table.Body>
                        {data.map((item) => (
                            <Table.Row key={item.id} id={item.id} selected={selections.includes(item.id)}>
                                {this.renderCells(item)}
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Pagination>
        );
    }
}
