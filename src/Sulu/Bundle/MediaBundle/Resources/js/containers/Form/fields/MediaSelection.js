// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import userStore from 'sulu-admin-bundle/stores/UserStore';
import {observable} from 'mobx';
import MultiMediaSelection from '../../MultiMediaSelection';
import type {Value} from '../../MultiMediaSelection';

export default @observer class MediaSelection extends React.Component<FieldTypeProps<Value>> {
    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {formInspector, disabled, value} = this.props;
        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        return (
            <MultiMediaSelection
                disabled={!!disabled}
                locale={locale}
                onChange={this.handleChange}
                value={value ? value : undefined}
            />
        );
    }
}
