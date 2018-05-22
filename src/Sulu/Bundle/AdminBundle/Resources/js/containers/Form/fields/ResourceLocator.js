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
        const {
            onChange,
            value,
            schemaOptions: {
                mode: {
                    value: mode,
                } = {value: 'leaf'},
            } = {},
            onFinish,
        } = this.props;

        if (mode !== 'leaf' && mode !== 'full') {
            throw new Error('The "mode" schema option must be either "leaf" or "full"!');
        }

        if (!value) {
            return null;
        }

        return (
            <ResourceLocatorComponent mode={mode} onBlur={onFinish} onChange={onChange} value={value} />
        );
    }
}
