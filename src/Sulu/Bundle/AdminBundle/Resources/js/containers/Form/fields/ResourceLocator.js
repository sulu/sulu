// @flow
import React from 'react';
import ResourceLocatorComponent from '../../../components/ResourceLocator';
import type {FieldTypeProps} from '../../../types';

export default class ResourceLocator extends React.Component<FieldTypeProps<string>> {
    constructor(props: FieldTypeProps<string>) {
        super(props);

        const {onChange, value} = this.props;

        if (value === undefined || value === '') {
            onChange('/');
        }
    }

    render() {
        const {onChange, value, schemaOptions, onFinish} = this.props;
        const mode = schemaOptions && schemaOptions.mode ? schemaOptions.mode : 'leaf';

        if (!value) {
            return null;
        }

        return (
            <ResourceLocatorComponent value={value} onChange={onChange} mode={mode} onBlur={onFinish} />
        );
    }
}
