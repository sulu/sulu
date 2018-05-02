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

    @computed get data(): Array<Object> {
        return this.props.data;
    }

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
        const {sortColumn, sortOrder} = this.props;
        const schemaKeys = Object.keys(this.schema);

        return schemaKeys.map((schemaKey) => {
            const label = this.schema[schemaKey].label ? this.schema[schemaKey].label : schemaKey;

            return(
                <Table.HeaderCell
                    key={schemaKey}
                    onClick={this.props.onSort}
                    name={schemaKey}
                    sortOrder={sortColumn === schemaKey ? sortOrder : undefined}
                >
                    {label}
                </Table.HeaderCell>
            );
        });
    }

    renderRows() {
        const {selections} = this.props;

        return this.data.map((item) => {
            return (
                <Table.Row key={item.id} id={item.id} selected={selections.includes(item.id)}>
                    {this.renderCells(item)}
                </Table.Row>
            );
        });
    }
}
