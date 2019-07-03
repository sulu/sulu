// @flow
import React from 'react';
import Router from '../../services/Router';
import type {ErrorCollection} from '../Form/types';
import {FormInspector, Renderer} from '../Form';
import type {Schema} from '../Form';

type Props = {|
    data: Object,
    dataPath: string,
    errors?: ErrorCollection,
    formInspector: FormInspector,
    index: number,
    onChange: (index: number, name: string, value: *) => void,
    onFieldFinish: ?() => void,
    router: ?Router,
    schema: Schema,
    schemaPath: string,
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
        const {
            data,
            dataPath,
            errors,
            formInspector,
            onFieldFinish,
            router,
            schema,
            schemaPath,
            showAllErrors,
        } = this.props;

        return (
            <Renderer
                data={data}
                dataPath={dataPath}
                errors={errors}
                formInspector={formInspector}
                onChange={this.handleChange}
                onFieldFinish={onFieldFinish}
                router={router}
                schema={schema}
                schemaPath={schemaPath}
                showAllErrors={showAllErrors}
            />
        );
    }
}
