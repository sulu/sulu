// @flow
import React from 'react';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import userStore from 'sulu-admin-bundle/stores/userStore';
import {observable} from 'mobx';
import {
    convertDisplayOptionsFromParams,
    convertMediaTypesFromParams,
    validateDisplayOption,
} from '../../../utils/MediaSelectionHelper';
import SingleMediaSelectionComponent from '../../SingleMediaSelection';
import type {Value} from '../../SingleMediaSelection';

@observer
class SingleMediaSelection extends React.Component<FieldTypeProps<Value>> {
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
            onChange({id: undefined, displayOption: defaultDisplayOption});
        }
    }

    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, error, formInspector, schemaOptions, value} = this.props;
        const {
            displayOptions: {
                value: displayOptions,
            } = {},
            types: {
                value: mediaTypes,
            } = {},
        } = schemaOptions;
        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        if (displayOptions !== undefined && displayOptions !== null && !Array.isArray(displayOptions)) {
            throw new Error('The "displayOptions" option has to be an Array if set.');
        }

        const displayOptionValues = convertDisplayOptionsFromParams(displayOptions);

        if (mediaTypes !== undefined && mediaTypes !== null && typeof mediaTypes !== 'string') {
            throw new Error('The "types" option has to be a string if set.');
        }

        const mediaTypeValues = convertMediaTypesFromParams(mediaTypes);

        return (
            <SingleMediaSelectionComponent
                disabled={!!disabled}
                displayOptions={displayOptionValues}
                locale={locale}
                onChange={this.handleChange}
                types={mediaTypeValues}
                valid={!error}
                value={value ? value : undefined}
            />
        );
    }
}

export default SingleMediaSelection;
