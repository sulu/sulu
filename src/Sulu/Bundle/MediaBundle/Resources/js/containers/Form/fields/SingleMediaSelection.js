// @flow
import React from 'react';
import {observer} from 'mobx-react';
import userStore from 'sulu-admin-bundle/stores/userStore';
import {computed, isArrayLike, observable} from 'mobx';
import {
    convertDisplayOptionsFromParams,
    convertMediaTypesFromParams,
    validateDisplayOption,
} from '../../../utils/MediaSelectionHelper';
import SingleMediaSelectionComponent from '../../SingleMediaSelection';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {Media} from '../../../types';
import type {Value} from '../../SingleMediaSelection';

@observer
class SingleMediaSelection extends React.Component<FieldTypeProps<Value>> {
    constructor(props: FieldTypeProps<Value>) {
        super(props);

        const {onChange, schemaOptions} = this.props;

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

        if (this.value === undefined) {
            onChange({id: undefined, displayOption: defaultDisplayOption}, {isDefaultValue: true});
        }
    }

    @computed get value(): ?Value {
        const {value, dataPath} = this.props;

        if (value && typeof value !== 'object') {
            throw new Error(
                'The "SingleMediaSelection" field with the path "' + dataPath + '" expects an object with an "id" '
                + 'property and an optional "displayOption" property as value. Is it possible that your API returns '
                + 'something else?'
                + '\n\nThe Sulu form view expects that your API returns the data in the same format as it is sent '
                + 'to the server when submitting the form.'
            );
        }

        return value;
    }

    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    handleItemClick = (itemId: ?number, item: ?Media) => {
        const {router} = this.props;

        if (!router || !item) {
            return;
        }

        const {id, locale} = item;

        router.navigate('sulu_media.form', {id, locale});
    };

    render() {
        const {disabled, error, formInspector, schemaOptions} = this.props;
        const {
            displayOptions: {
                value: displayOptions,
            } = {},
            types: {
                value: mediaTypes,
            } = {},
        } = schemaOptions;
        const locale = formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);

        if (displayOptions !== undefined && displayOptions !== null && !isArrayLike(displayOptions)) {
            throw new Error('The "displayOptions" option has to be an Array if set.');
        }

        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
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
                onItemClick={this.handleItemClick}
                types={mediaTypeValues}
                valid={!error}
                value={this.value ? this.value : undefined}
            />
        );
    }
}

export default SingleMediaSelection;
