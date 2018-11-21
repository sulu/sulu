// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {Value} from '../../MediaSelection/types';
import MediaSelectionComponent from '../../MediaSelection';

@observer
export default class MediaSelection extends React.Component<FieldTypeProps<Value>> {
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
            <MediaSelectionComponent
                disabled={!!disabled}
                locale={locale}
                onChange={this.handleChange}
                value={value ? value : undefined}
            />
        );
    }
}
