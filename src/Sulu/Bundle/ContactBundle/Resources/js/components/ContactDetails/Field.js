// @flow
import React from 'react';
import type {Node} from 'react';
import {Form, Icon} from 'sulu-admin-bundle/components';
import type {FormFieldTypes} from 'sulu-admin-bundle/types';
import fieldStyles from './field.scss';

type Props = {|
    children: Node,
    index: number,
    label?: string,
    onRemove: (index: number) => void,
    onTypeChange: (index: number, type: number) => void,
    type: number,
    types: FormFieldTypes,
|};

export default class Field extends React.Component<Props> {
    handleRemove = () => {
        const {index, onRemove} = this.props;
        onRemove(index);
    };

    handleTypeChange = (type: number) => {
        const {index, onTypeChange} = this.props;
        onTypeChange(index, type);
    };

    render() {
        const {children, label, type, types} = this.props;

        return (
            <Form.Field colSpan={6} label={label} onTypeChange={this.handleTypeChange} type={type} types={types}>
                <div className={fieldStyles.field}>
                    {children}
                    <Icon className={fieldStyles.removeIcon} name="su-trash-alt" onClick={this.handleRemove} />
                </div>
            </Form.Field>
        );
    }
}
