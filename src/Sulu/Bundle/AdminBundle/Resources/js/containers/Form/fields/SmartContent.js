// @flow
import React from 'react';
import {autorun, toJS} from 'mobx';
import equals from 'fast-deep-equal';
import type {FieldTypeProps} from '../../../types';
import SmartContentComponent, {SmartContentStore} from '../../SmartContent';
import type {FilterCriteria} from '../../SmartContent/types';

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

export default class SmartContent extends React.Component<Props> {
    smartContentStore: SmartContentStore;

    constructor(props: Props) {
        super(props);

        const {formInspector, value} = this.props;
        // TODO replace with "page" with correct value
        this.smartContentStore = new SmartContentStore(value, formInspector.locale, 'pages');

        autorun(this.handleFilterCriteriaChange);
    }

    componentWillUnmount() {
        this.smartContentStore.destroy();
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
    };

    render() {
        const {label} = this.props;

        return (
            <SmartContentComponent
                fieldLabel={label}
                store={this.smartContentStore}
            />
        );
    }
}
