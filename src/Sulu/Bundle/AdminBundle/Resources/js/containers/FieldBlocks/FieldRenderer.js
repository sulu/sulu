// @flow
import React from 'react';
import type {ErrorCollection} from '../../types';
import {FormInspector, Renderer} from '../Form';
import type {Schema} from '../Form';

type Props = {|
    data: Object,
    errors?: ErrorCollection,
    formInspector: FormInspector,
    index: number,
    onChange: (index: number, name: string, value: *) => void,
    onFieldFinish: ?() => void,
    schema: Schema,
    showAllErrors: boolean,
|};

export default class FieldRenderer extends React.Component<Props> {
    static defaultProps = {
        showAllErrors: false,
    };

    handleChange = (name: string, value: *) => {
        const {index, onChange} = this.props;
        onChange(index, name, value);
    };

    render() {
        const {data, errors, formInspector, onFieldFinish, schema, showAllErrors} = this.props;

        return (
            <Renderer
                data={data}
                errors={errors}
                formInspector={formInspector}
                onChange={this.handleChange}
                onFieldFinish={onFieldFinish}
                schema={schema}
                showAllErrors={showAllErrors}
            />
        );
    }
}
