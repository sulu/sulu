// @flow
import React from 'react';
import {observer} from 'mobx-react';
import log from 'loglevel';
import userStore from 'sulu-admin-bundle/stores/userStore';
import {computed, isArrayLike, observable} from 'mobx';
import {
    convertDisplayOptionsFromParams,
    convertMediaTypesFromParams,
    validateDisplayOption,
} from '../../../utils/MediaSelectionHelper';
import MultiMediaSelection from '../../MultiMediaSelection';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {Media} from '../../../types';
import type {Value} from '../../MultiMediaSelection';

@observer
class MediaSelection extends React.Component<FieldTypeProps<Value>> {
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
            onChange({ids: [], displayOption: defaultDisplayOption}, {isDefaultValue: true});
        }
    }

    @computed get value(): ?Value {
        const {value, dataPath} = this.props;

        if (value && isArrayLike(value)) {
            log.warn(
                'The "MediaSelection" field with the path "' + dataPath + '" expects an object with an "ids" '
                + 'property as value but received an array instead. Is it possible that your API returns an array of '
                + 'ids or an array serialized objects?'
                + '\n\nThe Sulu form view expects that your API returns the data in the same format as it is sent '
                + 'to the server when submitting the form. '
                + '\nSulu will try to extract the required data from the given array heuristically. '
                + 'This decreases performance and might lead to errors or other unexpected behaviour.'
            );

            // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
            return {ids: value.map((item) => item && typeof item === 'object' ? item.id : item)};
        }

        if (value && (typeof value !== 'object' || !isArrayLike(value.ids))) {
            throw new Error(
                'The "MediaSelection" field expects an object with an "ids" property and '
                + 'an optional "displayOption" property as value.'
            );
        }

        return value;
    }

    handleChange = (value: Value) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    handleItemClick = (itemId: string | number, item: ?Media) => {
        const {router} = this.props;

        if (!router || !item) {
            return;
        }

        const {id, locale} = item;

        router.navigate('sulu_media.form', {id, locale});
    };

    render() {
        const {disabled, formInspector, schemaOptions} = this.props;
        const {
            displayOptions: {
                value: displayOptions,
            } = {},
            types: {
                value: mediaTypes,
            } = {},
            sortable: {
                value: sortable = true,
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

        if (sortable !== undefined && typeof sortable !== 'boolean') {
            throw new Error('The "sortable" schema option must be a boolean if given!');
        }

        return (
            <MultiMediaSelection
                disabled={!!disabled}
                displayOptions={displayOptionValues}
                locale={locale}
                onChange={this.handleChange}
                onItemClick={this.handleItemClick}
                sortable={sortable}
                types={mediaTypeValues}
                value={this.value ? this.value : undefined}
            />
        );
    }
}

export default MediaSelection;
