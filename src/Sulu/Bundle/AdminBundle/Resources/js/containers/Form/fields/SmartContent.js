// @flow
import React from 'react';
import {computed, toJS, reaction, when} from 'mobx';
import equals from 'fast-deep-equal';
import type {FieldTypeProps} from '../../../types';
import SmartContentComponent, {smartContentConfigStore, SmartContentStore} from '../../SmartContent';
import type {FilterCriteria, Presentation} from '../../SmartContent/types';
import smartContentStorePool from './smartContentStorePool';

type Props = FieldTypeProps<?FilterCriteria>;

const filterCriteriaDefaults = {
    audienceTargeting: undefined,
    categories: undefined,
    categoryOperator: undefined,
    dataSource: undefined,
    includeSubFolders: undefined,
    limitResult: undefined,
    presentAs: undefined,
    sortBy: undefined,
    sortMethod: undefined,
    tagOperator: undefined,
    tags: undefined,
};

class SmartContent extends React.Component<Props> {
    smartContentStore: SmartContentStore;
    filterCriteriaChangeDisposer: () => mixed;

    @computed get previousSmartContentStores() {
        return smartContentStorePool.findPreviousStores(this.smartContentStore);
    }

    @computed get presentations(): Array<Presentation> {
        const {
            schemaOptions: {
                present_as: {
                    value: schemaPresentations = [],
                } = {},
            } = {},
        } = this.props;

        if (!Array.isArray(schemaPresentations)) {
            throw new Error(
                'The "present_as" schemaOption must be a string, but received ' + typeof schemaPresentations + '!'
            );
        }

        return schemaPresentations.map((presentation) => {
            const {name, title} = presentation;

            if (!name) {
                throw new Error('Every presentation in the "present_as" schema Option must contain a name');
            }

            if (!title) {
                throw new Error('Every presentation in the "present_as" schema Option must contain a title');
            }

            return {
                name: name.toString(),
                value: title.toString(),
            };
        });
    }

    @computed get provider() {
        const {
            schemaOptions: {
                provider: {
                    value: provider,
                } = {value: 'pages'},
            } = {},
        } = this.props;

        if (typeof provider !== 'string') {
            throw new Error('The "provider" schemaOption must be a string, but received ' + typeof provider + '!');
        }

        return provider;
    }

    @computed get value() {
        const {value} = this.props;

        return value !== undefined
            ? value
            : this.defaultValue;
    }

    @computed get defaultValue() {
        return smartContentConfigStore.getDefaultValue(
            this.provider,
            this.presentations
        );
    }

    constructor(props: Props) {
        super(props);

        const {
            formInspector,
            onChange,
            schemaOptions: {
                exclude_duplicates: {
                    value: excludeDuplicates = false,
                } = {},
            } = {},
            value,
        } = this.props;

        if (typeof excludeDuplicates !== 'boolean') {
            throw new Error('The "exclude_duplicates" schemaOption must be a boolean if set!');
        }

        const {datasourceResourceKey} = smartContentConfigStore.getConfig(this.provider);

        if (value === undefined) {
            onChange(this.value);
        }

        this.smartContentStore = new SmartContentStore(
            this.provider,
            this.value,
            formInspector.locale,
            datasourceResourceKey,
            formInspector.resourceKey === this.provider ? formInspector.id : undefined
        );

        smartContentStorePool.add(this.smartContentStore, excludeDuplicates);

        this.filterCriteriaChangeDisposer = reaction(
            () => toJS(this.smartContentStore.filterCriteria),
            (value): void => this.handleFilterCriteriaChange(value)
        );

        if (!excludeDuplicates || this.previousSmartContentStores.length === 0) {
            this.smartContentStore.start();
        } else {
            // If duplicates are excluded wait with loading the smart content until all previous ones have been loaded
            // Otherwise it is not known which ids to exclude for the initial request and has to be done a second time
            when(
                () => this.previousSmartContentStores.every((store) => !store.itemsLoading),
                (): void => {
                    smartContentStorePool.updateExcludedIds();
                    this.smartContentStore.start();
                }
            );
        }
    }

    componentWillUnmount() {
        smartContentStorePool.remove(this.smartContentStore);
        this.smartContentStore.destroy();
        this.filterCriteriaChangeDisposer();
    }

    handleFilterCriteriaChange = (filterCriteria: ?FilterCriteria) => {
        const {onChange, onFinish, value} = this.props;

        const currentValue = {...filterCriteriaDefaults, ...toJS(value)};
        const newValue = {...filterCriteriaDefaults, ...toJS(filterCriteria)};

        if (currentValue) {
            if (currentValue.categories) {
                currentValue.categories.sort();
            }

            if (currentValue.tags) {
                currentValue.tags.sort();
            }
        }

        if (newValue) {
            if (newValue.categories) {
                newValue.categories.sort();
            }

            if (newValue.tags) {
                newValue.tags.sort();
            }
        }

        if (this.smartContentStore.loading || equals(currentValue, newValue)) {
            return;
        }

        onChange(filterCriteria);
        onFinish();

        smartContentStorePool.updateExcludedIds();
    };

    render() {
        const {
            disabled,
            label,
            schemaOptions: {
                category_root: {
                    value: categoryRootKey,
                } = {},
            } = {},
        } = this.props;

        if (categoryRootKey !== undefined && typeof categoryRootKey !== 'string') {
            throw new Error('The "category_root" schemaOption must a string if set!');
        }

        return (
            <SmartContentComponent
                categoryRootKey={categoryRootKey}
                defaultValue={this.defaultValue}
                disabled={!!disabled}
                fieldLabel={label}
                presentations={this.presentations}
                store={this.smartContentStore}
            />
        );
    }
}

export default SmartContent;
