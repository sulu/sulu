// @flow
import {computed} from 'mobx';
import React from 'react';
import GhostIndicator from '../../../components/GhostIndicator';
import PublishIndicator from '../../../components/PublishIndicator';
import Table from '../../../components/Table';
import listFieldTransformerRegistry from '../registries/listFieldTransformerRegistry';
import AbstractAdapter from './AbstractAdapter';
import abstractTableAdapterStyles from './abstractTableAdapter.scss';
import type {Schema} from '../types';

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

        return schemaKeys.map((schemaKey, index) => {
            const transformer = listFieldTransformerRegistry.get(this.schema[schemaKey].type);
            const value = transformer.transform(
                item[schemaKey],
                this.schema[schemaKey].transformerTypeParameters,
                item
            );

            const indicators = [];
            if (index === 0) {
                if (item.ghostLocale) {
                    indicators.push(
                        <GhostIndicator
                            className={abstractTableAdapterStyles.ghostIndicator}
                            key="ghost"
                            locale={item.ghostLocale}
                        />
                    );
                } else {
                    if (item.publishedState !== undefined || item.published !== undefined) {
                        const draft = !item.publishedState;
                        const published = !!item.published;

                        if (draft || !published) {
                            indicators.push(
                                <PublishIndicator
                                    className={abstractTableAdapterStyles.publishIndicator}
                                    draft={draft}
                                    key="publish"
                                    published={published}
                                />
                            );
                        }
                    }
                }
            }

            return (
                <Table.Cell key={item.id + schemaKey}>
                    {indicators}
                    {value}
                </Table.Cell>
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
