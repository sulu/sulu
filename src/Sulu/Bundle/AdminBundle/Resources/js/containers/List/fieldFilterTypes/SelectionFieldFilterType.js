// @flow
import React from 'react';
import {action, autorun, computed, observable, toJS, untracked, when} from 'mobx';
import equals from 'fast-deep-equal';
import userStore from '../../../stores/userStore';
import MultiSelectionStore from '../../../stores/MultiSelectionStore';
import MultiAutoComplete from '../../MultiAutoComplete';
import ResourceCheckboxGroup from '../../ResourceCheckboxGroup';
import AbstractFieldFilterType from './AbstractFieldFilterType';
import selectionFieldFilterTypeStyles from './selectionFieldFilterType.scss';
import type {ElementRef} from 'react';

const TYPE_AUTO_COMPLETE = 'auto_complete';
const TYPE_SELECT = 'select';

class SelectionFieldFilterType extends AbstractFieldFilterType<?Array<string | number>> {
    selectionStore: MultiSelectionStore<string | number>;
    selectionStoreDisposer: () => void;
    // Used to buffer the select value, because everytime the value variable changes a request is sent to load data
    @observable selectValue: Array<string | number> = [];
    valueDisposer: () => void;

    @computed get type() {
        return this.parameters && (this.parameters.type || TYPE_AUTO_COMPLETE);
    }

    constructor(
        onChange: (value: ?Array<string | number>) => void,
        parameters: ?{[string]: mixed},
        value: ?Array<string | number>
    ) {
        super(onChange, parameters, value);

        this.selectionStore = new MultiSelectionStore(
            this.resourceKey,
            [],
            observable.box(userStore.contentLocale),
            'ids',
            this.resourceParameters
        );

        this.selectionStoreDisposer = autorun(() => {
            const {onChange, selectionStore} = this;

            if (selectionStore.ids.length === 0) {
                onChange(undefined);
                return;
            }

            onChange(selectionStore.ids);
        });

        this.valueDisposer = autorun(() => {
            const value = toJS(this.value || []);

            if (!equals(value, untracked(() => toJS(this.selectionStore.ids)))) {
                this.selectionStore.loadItems(value);
            }

            if (!equals(value, untracked(() => this.selectValue))) {
                this.setSelectValue(value);
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
            throw new Error('The "resourceKey" parameters must be a string!');
        }

        return resourceKey;
    }

    @computed get resourceParameters() {
        const {parameters} = this;

        if (!parameters) {
            throw new Error('The "SelectionFieldFilterType" needs some parameters to work!');
        }

        const {requestParameters} = parameters;

        if (typeof requestParameters !== 'object') {
            return {};
        }

        const resourceParameters = {};
        for (const parameter in requestParameters) {
            resourceParameters[parameter] = requestParameters[parameter];
        }

        return resourceParameters;
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

    setInputRef(ref: ?ElementRef<'input'>) {
        if (ref) {
            ref.focus();
        }
    }

    @action setSelectValue = (values: Array<string | number>) => {
        this.selectValue = values;
    };

    handleSelectChange = (values: Array<string | number>) => {
        this.setSelectValue(values);
    };

    confirm = () => {
        this.onChange(this.selectValue);
    };

    getFormNode() {
        return (
            <div className={selectionFieldFilterTypeStyles.selectionFieldFilterType}>
                {this.type === TYPE_AUTO_COMPLETE &&
                    <MultiAutoComplete
                        displayProperty={this.displayProperty}
                        inputRef={this.setInputRef}
                        searchProperties={[this.displayProperty]}
                        selectionStore={this.selectionStore}
                    />
                }
                {this.type === TYPE_SELECT &&
                    <ResourceCheckboxGroup
                        displayProperty={this.displayProperty}
                        onChange={this.handleSelectChange}
                        resourceKey={this.resourceKey}
                        values={this.selectValue}
                    />
                }
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
