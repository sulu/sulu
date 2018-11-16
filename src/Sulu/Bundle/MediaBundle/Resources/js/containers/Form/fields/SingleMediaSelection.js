// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import SingleMediaSelectionComponent from '../../SingleMediaSelection';

@observer
export default class SingleMediaSelection extends React.Component<FieldTypeProps<?number>> {
    handleChange = (mediaId: ?number) => {
        const {onChange, onFinish} = this.props;

        onChange(mediaId);
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
                value={value}
            />
        );
    }
}
