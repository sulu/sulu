// @flow
import React from 'react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import SegmentSelectContainer from '../../SegmentSelect';
import type {Value} from '../../SegmentSelect/types';

export default class SegmentSelect extends React.Component<FieldTypeProps<Value>> {
    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, formInspector, value} = this.props;

        return (
            <SegmentSelectContainer
                disabled={disabled}
                onChange={this.handleChange}
                value={value}
                webspace={formInspector.metadataOptions?.webspace}
            />
        );
    }
}
