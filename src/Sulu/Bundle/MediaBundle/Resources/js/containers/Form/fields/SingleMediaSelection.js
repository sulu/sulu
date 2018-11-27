// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import SingleMediaSelectionComponent from '../../SingleMediaSelection';
import type {Value} from '../../SingleMediaSelection';

@observer
export default class SingleMediaSelection extends React.Component<FieldTypeProps<Value>> {
    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {formInspector, disabled, value} = this.props;

        if (!formInspector || !formInspector.locale) {
            throw new Error('The media selection needs a locale to work properly');
        }

        const {locale} = formInspector;

        return (
            <SingleMediaSelectionComponent
                disabled={!!disabled}
                locale={locale}
                onChange={this.handleChange}
                value={value ? value : undefined}
            />
        );
    }
}
