// @flow
import React from 'react';
import {autorun, computed, toJS, untracked, when} from 'mobx';
import equals from 'fast-deep-equal';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import MultiAutoComplete from '../../MultiAutoComplete';
import AbstractFieldFilterType from './AbstractFieldFilterType';
import selectionFieldFilterTypeStyles from './selectionFieldFilterType.scss';

class SelectionFieldFilterType extends AbstractFieldFilterType<?Array<string | number>> {
    selectionStore: MultiSelectionStore<string | number>;
    selectionStoreDisposer: () => void;
    valueDisposer: () => void;

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

        this.selectionStore = new MultiSelectionStore(resourceKey, []);

        this.selectionStoreDisposer = autorun(() => {
            const {onChange, selectionStore} = this;

            if (selectionStore.ids.length === 0) {
                onChange(undefined);
                return;
            }

            onChange(selectionStore.ids);
        });

        this.valueDisposer = autorun(() => {
            const {value = []} = this;

            if (!equals(toJS(value), untracked(() => this.selectionStore.ids))) {
                this.selectionStore.loadItems(value);
            }
        });
    }

    destroy() {
        this.selectionStoreDisposer();
        this.valueDisposer();
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

    getFormNode() {
        return (
            <div className={selectionFieldFilterTypeStyles.selectionFieldFilterType}>
                <MultiAutoComplete
                    displayProperty={this.displayProperty}
                    searchProperties={[this.displayProperty]}
                    selectionStore={this.selectionStore}
                />
            </div>
        );
    }

    getValueNode(value: ?Array<string | number>) {
        if (!value) {
            return Promise.resolve(null);
        }

        return new Promise<string>((resolve) => {
            when(
                () => !this.selectionStore.loading,
                () => resolve(
                    value.map(
                        (id) => {
                            const item = this.selectionStore.getById(id);

                            return item ? item[this.displayProperty] : '';
                        }
                    ).join(', ')
                )
            );
        });
    }
}

export default SelectionFieldFilterType;
