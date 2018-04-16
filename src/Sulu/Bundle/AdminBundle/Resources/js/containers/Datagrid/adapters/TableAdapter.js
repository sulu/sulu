// @flow
import {observer} from 'mobx-react';
import React from 'react';
import Pagination from '../../../components/Pagination';
import Table from '../../../components/Table';
import {translate} from '../../../utils/Translator';
import PaginatedLoadingStrategy from '../loadingStrategies/PaginatedLoadingStrategy';
import FlatStructureStrategy from '../structureStrategies/FlatStructureStrategy';
import AbstractAdapter from './AbstractAdapter';

@observer
export default class TableAdapter extends AbstractAdapter {
    static LoadingStrategy = PaginatedLoadingStrategy;

    static StructureStrategy = FlatStructureStrategy;

    static icon = 'su-align-justify';

    static defaultProps = {
        data: [],
    };

    renderCells(item: Object, schemaKeys: Array<string>) {
        return schemaKeys.map((schemaKey) => {
            // TODO: Remove this when a datafield mapping is built
            if (typeof item[schemaKey] === 'object') {
                return <Table.Cell key={item.id + schemaKey}>Object!</Table.Cell>;
            }

            return (
                <Table.Cell key={item.id + schemaKey}>{item[schemaKey]}</Table.Cell>
            );
        });
    }

    renderHeaderCells(schema: Object, schemaKeys: Array<string>) {
        return schemaKeys.map((schemaKey) => {
            const title = schema[schemaKey] && schema[schemaKey].title ? translate(schema[schemaKey].title) : schemaKey;

            return(
                <Table.HeaderCell key={schemaKey}>
                    {title}
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
            schema,
            selections,
        } = this.props;
        const schemaKeys = Object.keys(schema);
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
                        {this.renderHeaderCells(schema, schemaKeys)}
                    </Table.Header>
                    <Table.Body>
                        {data.map((item) => (
                            <Table.Row key={item.id} id={item.id} selected={selections.includes(item.id)}>
                                {this.renderCells(item, schemaKeys)}
                            </Table.Row>
                        ))}
                    </Table.Body>
                </Table>
            </Pagination>
        );
    }
}
