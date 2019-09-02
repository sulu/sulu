// @flow
import {computed} from 'mobx';
import React from 'react';
import Table from '../../../components/Table';
import listFieldTransformerRegistry from '../registries/listFieldTransformerRegistry';
import type {Schema} from '../types';
import AbstractAdapter from './AbstractAdapter';

export default class AbstractTableAdapter extends AbstractAdapter {
    static hasColumnOptions: boolean = true;

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
            const transformer = listFieldTransformerRegistry.get(this.schema[schemaKey].type);
            const value = transformer.transform(item[schemaKey]);

            return (
                <Table.Cell key={item.id + schemaKey}>{value}</Table.Cell>
            );
        });
    }

    renderHeaderCells(): Array<*> {
        const {onSort, sortColumn, sortOrder} = this.props;
        const schemaKeys = Object.keys(this.schema);

        return schemaKeys.map((schemaKey) => {
            const columnSchema = this.schema[schemaKey];
            const label = columnSchema.label ? columnSchema.label : schemaKey;

            return (
                <Table.HeaderCell
                    key={schemaKey}
                    name={schemaKey}
                    onClick={columnSchema.sortable ? onSort : undefined}
                    sortOrder={sortColumn === schemaKey ? sortOrder : undefined}
                >
                    {label}
                </Table.HeaderCell>
            );
        });
    }
}
