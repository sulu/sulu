// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import CustomUrlComponent from '../../../components/CustomUrl';

@observer
export default class CustomUrl extends React.Component<FieldTypeProps<Array<?string>>> {
    handleChange = (value: Array<?string>) => {
        const {onChange} = this.props;

        onChange(value);
    };

    handleBlur = () => {
        const {onFinish} = this.props;

        onFinish();
    };

    render() {
        const {formInspector, value} = this.props;

        const baseDomain = formInspector.getValueByPath('/baseDomain');

        if (typeof baseDomain !== 'string') {
            throw new Error('The baseDomain should be a string. This should not happen and is likely a bug.');
        }

        return (
            <CustomUrlComponent
                baseDomain={baseDomain}
                onBlur={this.handleBlur}
                onChange={this.handleChange}
                value={value || []}
            />
        );
    }
}
