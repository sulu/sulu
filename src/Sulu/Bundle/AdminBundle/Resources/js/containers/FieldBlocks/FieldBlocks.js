// @flow
import React from 'react';
import {toJS} from 'mobx';
import BlockCollection from '../../components/BlockCollection';
import type {BlockEntry} from '../../components/BlockCollection/types';
import type {FieldTypeProps} from '../../types';
import FieldRenderer from './FieldRenderer';

const MISSING_BLOCK_ERROR_MESSAGE = 'The "block" field type needs at least one type to be configured!';

export default class FieldBlocks extends React.Component<FieldTypeProps<Array<BlockEntry>>> {
    handleBlockChange = (index: number, name: string, value: Object) => {
        const {onChange, value: oldValues} = this.props;

        const newValues = toJS(oldValues);
        newValues[index][name] = value;

        onChange(newValues);
    };

    handleSortEnd = () => {
        this.props.onFinish();
    };

    renderBlockContent = (value: Object, type: ?string, index: number) => {
        const {error, locale, onFinish, types} = this.props;

        if (!types) {
            throw new Error(MISSING_BLOCK_ERROR_MESSAGE);
        }

        const blockType = type ? types[type] : types[Object.keys(types)[0]]; // TODO replace with a default type

        return (
            <FieldRenderer
                data={value}
                errors={error && error[index] ? error[index] : undefined}
                index={index}
                locale={locale}
                onChange={this.handleBlockChange}
                onFieldFinish={onFinish}
                schema={blockType.form}
            />
        );
    };

    render() {
        const {maxOccurs, minOccurs, onChange, types, value} = this.props;

        if (!types) {
            throw new Error(MISSING_BLOCK_ERROR_MESSAGE);
        }

        const blockTypes = Object.keys(types).reduce((blockTypes, current) => {
            blockTypes[current] = types[current].title;
            return blockTypes;
        }, {});

        return (
            <BlockCollection
                maxOccurs={maxOccurs}
                minOccurs={minOccurs}
                onChange={onChange}
                onSortEnd={this.handleSortEnd}
                renderBlockContent={this.renderBlockContent}
                types={blockTypes}
                value={value || []}
            />
        );
    }
}
