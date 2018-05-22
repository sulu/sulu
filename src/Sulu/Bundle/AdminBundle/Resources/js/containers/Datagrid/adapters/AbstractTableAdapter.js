// @flow
import {computed} from 'mobx';
import React from 'react';
import Table from '../../../components/Table';
import datagridFieldTransformerRegistry from '../registries/DatagridFieldTransformerRegistry';
import type {Schema} from '../types';
import AbstractAdapter from './AbstractAdapter';

export default class AbstractTableAdapter extends AbstractAdapter {
    static defaultProps = {
        data: [],
    };

    @computed get schema(): Schema {
        const {schema} = this.props;

        const newSchema = {};

        for (const key of Object.keys(schema)) {
            if (schema[key].visibility === 'never' || schema[key].visibility === 'no') {
                continue;
            }

            newSchema[key] = schema[key];
        }

        return newSchema;
    }

    renderCells(item: Object): Array<*> {
        const schemaKeys = Object.keys(this.schema);

        return schemaKeys.map((schemaKey) => {
            const transformer = datagridFieldTransformerRegistry.get(this.schema[schemaKey].type);
            const value = transformer.transform(item[schemaKey]);

            return (
                <Table.Cell key={item.id + schemaKey}>{value}</Table.Cell>
            );
        });
    }

    renderHeaderCells(sortingEnabled: boolean): Array<*> {
        const {sortColumn, sortOrder} = this.props;
        const schemaKeys = Object.keys(this.schema);

        return schemaKeys.map((schemaKey) => {
            const label = this.schema[schemaKey].label ? this.schema[schemaKey].label : schemaKey;

            return(
                <Table.HeaderCell
                    key={schemaKey}
                    name={schemaKey}
                    onClick={sortingEnabled ? this.props.onSort : undefined}
                    sortOrder={sortColumn === schemaKey ? sortOrder : undefined}
                >
                    {label}
                </Table.HeaderCell>
            );
        });
    }

    renderRows() {
        const {data, selections} = this.props;

        return data.map((item) => {
            return (
                <Table.Row id={item.id} key={item.id} selected={selections.includes(item.id)}>
                    {this.renderCells(item)}
                </Table.Row>
            );
        });
    }
}
