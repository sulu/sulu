// @flow
import React from 'react';
import ResourceLocatorComponent from '../../../components/ResourceLocator';
import type {FieldTypeProps} from '../../../types';

export default class ResourceLocator extends React.Component<FieldTypeProps<string>> {
    componentWillMount() {
        const {onChange, value} = this.props;

        if (value === undefined) {
            onChange('/');
        }
    }

    render() {
        const {onChange, value, options, onFinish} = this.props;
        const mode = options && options.mode ? options.mode : 'leaf';

        if (!value) {
            return null;
        }

        return (
            <ResourceLocatorComponent value={value} onChange={onChange} mode={mode} onFinish={onFinish} />
        );
    }
}
