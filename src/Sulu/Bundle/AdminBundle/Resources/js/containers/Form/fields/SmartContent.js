// @flow
import React from 'react';
import {autorun, computed, toJS, when} from 'mobx';
import equals from 'fast-deep-equal';
import type {FieldTypeProps} from '../../../types';
import SmartContentComponent, {smartContentConfigStore, SmartContentStore} from '../../SmartContent';
import type {FilterCriteria} from '../../SmartContent/types';
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
    filterCriteriaChangeDisposer: () => void;

    @computed get previousSmartContentStores() {
        const previousSmartContentStores = [];
        for (const smartContentStore of smartContentStorePool.stores) {
            if (smartContentStore === this.smartContentStore) {
                break;
            }

            previousSmartContentStores.push(smartContentStore);
        }

        return previousSmartContentStores;
    }

    constructor(props: Props) {
        super(props);

        const {
            formInspector,
            schemaOptions: {
                exclude_duplicates: {
                    value: excludeDuplicates = false,
                } = {},
                provider: {
                    value: provider,
                } = {value: 'pages'},
            } = {},
            value,
        } = this.props;

        if (typeof provider !== 'string') {
            throw new Error('The "provider" schemaOption must be a string, but received ' + typeof provider + '!');
        }

        if (typeof excludeDuplicates !== 'boolean') {
            throw new Error('The "exclude_duplicates" schemaOption must be a boolean if set!');
        }

        const datasourceResourceKey = smartContentConfigStore.getConfig(provider).datasourceResourceKey;

        this.smartContentStore = new SmartContentStore(
            provider,
            value,
            formInspector.locale,
            datasourceResourceKey,
            formInspector.resourceKey === provider ? formInspector.id : undefined
        );

        smartContentStorePool.add(this.smartContentStore, excludeDuplicates);

        this.filterCriteriaChangeDisposer = autorun(this.handleFilterCriteriaChange);

        if (!excludeDuplicates || this.previousSmartContentStores.length === 0) {
            this.smartContentStore.start();
        } else {
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

    handleFilterCriteriaChange = () => {
        const {onChange, onFinish, value} = this.props;

        const currentValue = {...filterCriteriaDefaults, ...toJS(value)};
        const newValue = {...filterCriteriaDefaults, ...toJS(this.smartContentStore.filterCriteria)};

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

        onChange(this.smartContentStore.filterCriteria);
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

        if (categoryRootKey !== undefined && typeof categoryRootKey !== 'string') {
            throw new Error('The "category_root" schemaOption must a string if set!');
        }

        const presentations = schemaPresentations.map((presentation) => {
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

        return (
            <SmartContentComponent
                categoryRootKey={categoryRootKey}
                disabled={!!disabled}
                fieldLabel={label}
                presentations={presentations}
                store={this.smartContentStore}
            />
        );
    }
}

export default SmartContent;
