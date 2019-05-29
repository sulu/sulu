// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import userStore from 'sulu-admin-bundle/stores/UserStore';
import {observable} from 'mobx';
import {convertDisplayOptionsFromParams, validateDisplayOption} from '../../../utils/MediaSelectionHelper';
import MultiMediaSelection from '../../MultiMediaSelection';
import type {Value} from '../../MultiMediaSelection';

@observer
class MediaSelection extends React.Component<FieldTypeProps<Value>> {
    constructor(props: FieldTypeProps<Value>) {
        super(props);

        const {onChange, schemaOptions, value} = this.props;

        const {
            defaultDisplayOption: {
                value: defaultDisplayOption,
            } = {},
        } = schemaOptions;

        if (!defaultDisplayOption) {
            return;
        }

        if (typeof defaultDisplayOption !== 'string' || !validateDisplayOption(defaultDisplayOption)) {
            throw new Error(
                'The children of "defaultDisplayOption" contains the invalid value "'
                + (defaultDisplayOption.toString() + '') + '".'
            );
        }

        if (value === undefined) {
            onChange({ids: [], displayOption: defaultDisplayOption});
        }
    }

    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, formInspector, schemaOptions, value} = this.props;
        const {
            displayOptions: {
                value: displayOptions,
            } = {},
        } = schemaOptions;
        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        if (displayOptions !== undefined && displayOptions !== null && !Array.isArray(displayOptions)) {
            throw new Error('The "displayOptions" option has to be an Array if set.');
        }

        const displayOptionValues = convertDisplayOptionsFromParams(displayOptions);

        return (
            <MultiMediaSelection
                disabled={!!disabled}
                displayOptions={displayOptionValues}
                locale={locale}
                onChange={this.handleChange}
                value={value ? value : undefined}
            />
        );
    }
}

export default MediaSelection;
