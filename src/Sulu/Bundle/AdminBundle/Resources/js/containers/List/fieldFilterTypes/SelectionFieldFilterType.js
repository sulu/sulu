// @flow
import React from 'react';
import {computed, toJS, when} from 'mobx';
import equals from 'fast-deep-equal';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import MultiAutoComplete from '../../MultiAutoComplete';
import AbstractFieldFilterType from './AbstractFieldFilterType';
import selectionFieldFilterTypeStyles from './selectionFieldFilterType.scss';

class SelectionFieldFilterType extends AbstractFieldFilterType<?Array<string | number>> {
    selectionStore: MultiSelectionStore<string | number>;

    constructor(
        onChange: (value: ?Array<string | number>) => void,
        parameters: ?{[string]: mixed},
        value: ?Array<string | number>
    ) {
        super(onChange, parameters, value);

        if (!parameters) {
            throw new Error('The "SelectionFieldFilterType" needs some parameters to work!');
        }

        const {resourceKey} = parameters;

        if (typeof resourceKey !== 'string') {
            throw new Error('The "resourceKey" parameters must be a string!');
        }

        this.selectionStore = new MultiSelectionStore(resourceKey, value || []);
    }

    @computed get resourceKey() {
        const {parameters} = this;

        if (!parameters) {
            throw new Error('The "SelectionFieldFilterType" needs some parameters to work!');
        }

        const {resourceKey} = parameters;

        if (typeof resourceKey !== 'string') {
            throw new Error('The "resourceKey" parameter must be a string!');
        }

        return resourceKey;
    }

    @computed get displayProperty() {
        const {parameters} = this;

        if (!parameters) {
            throw new Error('The "SelectionFieldFilterType" needs some parameters to work!');
        }

        const {displayProperty} = parameters;

        if (typeof displayProperty !== 'string') {
            throw new Error('The "displayProperty" parameter must be a string!');
        }

        return displayProperty;
    }

    handleChange = (value: ?Array<string | number>) => {
        const {onChange} = this;

        if (value && value.length === 0) {
            onChange(undefined);
            return;
        }

        onChange(value);
    };

    getFormNode() {
        const {value} = this;

        return (
            <div className={selectionFieldFilterTypeStyles.selectionFieldFilterType}>
                <MultiAutoComplete
                    displayProperty={this.displayProperty}
                    onChange={this.handleChange}
                    resourceKey={this.resourceKey}
                    searchProperties={[this.displayProperty]}
                    value={value || []}
                />
            </div>
        );
    }

    getValueNode(value: ?Array<string | number>) {
        if (!value) {
            this.selectionStore.loadItems([]);
            return Promise.resolve(null);
        }

        return new Promise<string>((resolve) => {
            if (!equals(toJS(value), this.selectionStore.ids)) {
                this.selectionStore.loadItems(value);
            }

            when(
                () => !this.selectionStore.loading,
                () => resolve(this.selectionStore.items.map((item) => item[this.displayProperty]).join(', '))
            );
        });
    }
}

export default SelectionFieldFilterType;
