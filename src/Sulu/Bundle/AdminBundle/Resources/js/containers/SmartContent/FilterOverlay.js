// @flow
import React, {Fragment} from 'react';
import {action, autorun, observable} from 'mobx';
import {observer} from 'mobx-react';
import Button from '../../components/Button';
import Toggler from '../../components/Toggler';
import Number from '../../components/Number';
import SingleSelect from '../../components/SingleSelect';
import Overlay from '../../components/Overlay';
import MultiDatagridOverlay from '../../containers/MultiDatagridOverlay';
import SingleDatagridOverlay from '../../containers/SingleDatagridOverlay';
import MultiAutoComplete from '../../containers/MultiAutoComplete';
import {translate} from '../../utils/Translator';
import SmartContentStore from './stores/SmartContentStore';
import type {Conjunction, SortOrder} from './types';
import filterOverlayStyles from './filterOverlay.scss';

type Props = {
    dataSourceAdapter: ?string,
    dataSourceDatagridKey: ?string,
    dataSourceResourceKey: ?string,
    onClose: () => void,
    open: boolean,
    presentations: {[key: string]: string},
    smartContentStore: SmartContentStore,
    sections: Array<string>,
    sortings: {[key: string]: string},
    title: string,
};

@observer
export default class FilterOverlay extends React.Component<Props> {
    @observable dataSource: ?Object;
    @observable includeSubElements: ?boolean;
    @observable categories: ?Array<Object>;
    @observable categoryOperator: ?Conjunction;
    @observable tags: ?Array<string | number>;
    @observable tagOperator: ?Conjunction;
    @observable audienceTargeting: ?boolean;
    @observable sortBy: ?string;
    @observable sortOrder: ?SortOrder;
    @observable limit: ?number;
    @observable presentation: ?string;
    @observable showDataSourceDialog: boolean = false;
    @observable showCategoryDialog: boolean = false;
    updateFilterCriteriaDisposer: () => void;

    constructor(props: Props) {
        super(props);

        this.updateFilterCriteriaDisposer = autorun(() => this.updateFilterCriteria(this.props.smartContentStore));
    }

    componentWillUnmount() {
        this.updateFilterCriteriaDisposer();
    }

    @action updateFilterCriteria = (smartContentStore: SmartContentStore) => {
        this.dataSource = smartContentStore.dataSource;
        this.includeSubElements = smartContentStore.includeSubElements;
        this.categories = smartContentStore.categories;
        this.categoryOperator = smartContentStore.categoryOperator;
        this.tags = smartContentStore.tags;
        this.tagOperator = smartContentStore.tagOperator;
        this.audienceTargeting = smartContentStore.audienceTargeting;
        this.sortBy = smartContentStore.sortBy;
        this.sortOrder = smartContentStore.sortOrder;
        this.presentation = smartContentStore.presentation;
        this.limit = smartContentStore.limit;
    };

    @action handleConfirm = () => {
        const {onClose, smartContentStore} = this.props;

        smartContentStore.audienceTargeting = this.audienceTargeting;
        smartContentStore.categories = this.categories;
        smartContentStore.categoryOperator = this.categoryOperator;
        smartContentStore.dataSource = this.dataSource;
        smartContentStore.includeSubElements = this.includeSubElements;
        smartContentStore.limit = this.limit;
        smartContentStore.sortBy = this.sortBy;
        smartContentStore.sortOrder = this.sortOrder;
        smartContentStore.tagOperator = this.tagOperator;
        smartContentStore.tags = this.tags;
        smartContentStore.presentation = this.presentation;

        onClose();
    };

    @action resetFilterCriteria = () => {
        this.dataSource = undefined;
        this.includeSubElements = undefined;
        this.categories = undefined;
        this.categoryOperator = undefined;
        this.tags = undefined;
        this.tagOperator = undefined;
        this.audienceTargeting = undefined;
        this.sortBy = undefined;
        this.sortOrder = undefined;
        this.presentation = undefined;
        this.limit = undefined;
    };

    @action handleConfirmDataSourceDialog = (dataSource: Object) => {
        this.dataSource = dataSource;
        this.showDataSourceDialog = false;
    };

    @action handleDataSourceButtonClick = () => {
        this.showDataSourceDialog = true;
    };

    @action handleCloseDataSourceDialog = () => {
        this.showDataSourceDialog = false;
    };

    @action handleCategoryButtonClick = () => {
        this.showCategoryDialog = true;
    };

    @action handleCloseCategoryDialog = () => {
        this.showCategoryDialog = false;
    };

    @action handleIncludeSubElementsChange = (includeSubElementsChange: boolean) => {
        this.includeSubElements = includeSubElementsChange;
    };

    @action handleConfirmCategoryDialog = (categories: Array<Object>) => {
        this.categories = categories;
        this.showCategoryDialog = false;
    };

    @action handleCategoryOperatorChange = (categoryOperator: string | number) => {
        if (categoryOperator !== 'or' && categoryOperator !== 'and') {
            throw new Error(
                'The tag operator must either be "or" or "and", but "' + categoryOperator + '" was given.'
                + ' This should not happen and is likely a bug.'
            );
        }

        this.categoryOperator = categoryOperator;
    };

    @action handleTagsChange = (tags: Array<string | number>) => {
        this.tags = tags;
    };

    @action handleTagOperatorChange = (tagOperator: string | number) => {
        if (tagOperator !== 'or' && tagOperator !== 'and') {
            throw new Error(
                'The tag operator must either be "or" or "and", but "' + tagOperator + '" was given.'
                + ' This should not happen and is likely a bug.'
            );
        }

        this.tagOperator = tagOperator;
    };

    @action handleAudienceTargetingChange = (audienceTargeting: boolean) => {
        this.audienceTargeting = audienceTargeting;
    };

    @action handleSortByChange = (sortBy: string | number) => {
        if (typeof sortBy !== 'string') {
            throw new Error(
                'The field for sorting must be a string, but "' + sortBy + '" was given.'
                + ' This should not happen and is likely a bug.'
            );
        }

        this.sortBy = sortBy;
    };

    @action handleSortOrderChange = (sortOrder: string | number) => {
        if (sortOrder !== 'asc' && sortOrder !== 'desc') {
            throw new Error(
                'The sort order is only allowed to be "asc" or "desc", but "' + sortOrder + '" was given.'
                + ' This should not happen and is likely a bug.'
            );
        }
        this.sortOrder = sortOrder;
    };

    @action handlePresentationChange = (presentation: string | number) => {
        if (typeof presentation !== 'string') {
            throw new Error(
                'The presentation must be represented as a string, but "' + presentation + '" was given.'
                + ' This should not happen and is likely a bug.'
            );
        }

        this.presentation = presentation;
    };

    @action handleLimitChange = (limit: ?number) => {
        this.limit = limit;
    };

    render() {
        const {
            dataSourceAdapter,
            dataSourceDatagridKey,
            dataSourceResourceKey,
            onClose,
            open,
            presentations,
            sections,
            smartContentStore,
            sortings,
            title,
        } = this.props;

        return (
            <Fragment>
                <Overlay
                    actions={[
                        {
                            title: translate('sulu_admin.reset'),
                            onClick: this.resetFilterCriteria,
                        },
                    ]}
                    confirmText={translate('sulu_admin.confirm')}
                    onClose={onClose}
                    onConfirm={this.handleConfirm}
                    open={open}
                    size="small"
                    title={title}
                >
                    <div className={filterOverlayStyles.content}>
                        {sections.includes('datasource') &&
                            <section className={filterOverlayStyles.section}>
                                <h3>{translate('sulu_admin.data_source')}</h3>
                                <div className={filterOverlayStyles.source}>
                                    <Button
                                        className={filterOverlayStyles.sourceButton}
                                        onClick={this.handleDataSourceButtonClick}
                                    >
                                        {translate('sulu_admin.choose_data_source')}
                                    </Button>
                                    <Toggler
                                        checked={this.includeSubElements || false}
                                        onChange={this.handleIncludeSubElementsChange}
                                    >
                                        {translate('sulu_admin.include_sub_elements')}
                                    </Toggler>
                                </div>
                                <label className={filterOverlayStyles.description}>
                                    {/* TODO do not hardcode "title" */}
                                    {translate('sulu_admin.data_source')}: {this.dataSource && this.dataSource.title}
                                </label>
                            </section>
                        }

                        {sections.includes('categories') &&
                            <section className={filterOverlayStyles.section}>
                                <h3>{translate('sulu_admin.filter_by_categories')}</h3>
                                <div className={filterOverlayStyles.categories}>
                                    <Button onClick={this.handleCategoryButtonClick}>
                                        {translate('sulu_admin.choose_categories')}
                                    </Button>
                                    <div className={filterOverlayStyles.categoriesSelect}>
                                        <SingleSelect
                                            onChange={this.handleCategoryOperatorChange}
                                            value={this.categoryOperator}
                                        >
                                            <SingleSelect.Option value="or">
                                                {translate('sulu_admin.any_category_description')}
                                            </SingleSelect.Option>
                                            <SingleSelect.Option value="and">
                                                {translate('sulu_admin.all_categories_description')}
                                            </SingleSelect.Option>
                                        </SingleSelect>
                                    </div>
                                </div>
                                <label className={filterOverlayStyles.description}>
                                    {translate('sulu_category.categories')}: {this.categories &&
                                        this.categories.map((category) => category.name).join(', ')
                                    }
                                </label>
                            </section>
                        }

                        {sections.includes('tags') &&
                            <section className={filterOverlayStyles.section}>
                                <h3>{translate('sulu_admin.filter_by_tags')}</h3>
                                <div className={filterOverlayStyles.tags}>
                                    <div className={filterOverlayStyles.tagsAutoComplete}>
                                        <MultiAutoComplete
                                            displayProperty="name"
                                            filterParameter="names"
                                            idProperty="name"
                                            onChange={this.handleTagsChange}
                                            resourceKey="tags"
                                            searchProperties={['name']}
                                            value={this.tags}
                                        />
                                    </div>
                                    <div className={filterOverlayStyles.tagsSelect}>
                                        <SingleSelect onChange={this.handleTagOperatorChange} value={this.tagOperator}>
                                            <SingleSelect.Option value="or">
                                                {translate('sulu_admin.any_tag_description')}
                                            </SingleSelect.Option>
                                            <SingleSelect.Option value="and">
                                                {translate('sulu_admin.all_tags_description')}
                                            </SingleSelect.Option>
                                        </SingleSelect>
                                    </div>
                                </div>
                            </section>
                        }

                        {sections.includes('audienceTargeting') &&
                            <section className={filterOverlayStyles.section}>
                                <h3>{translate('sulu_admin.target_groups')}</h3>
                                <Toggler
                                    checked={this.audienceTargeting || false}
                                    onChange={this.handleAudienceTargetingChange}
                                >
                                    {translate('sulu_admin.use_target_groups')}
                                </Toggler>
                            </section>
                        }

                        {sections.includes('sorting') &&
                            <section className={filterOverlayStyles.section}>
                                <h3>{translate('sulu_admin.sort_by')}</h3>
                                <div className={filterOverlayStyles.sorting}>
                                    <div className={filterOverlayStyles.sortColumn}>
                                        <SingleSelect onChange={this.handleSortByChange} value={this.sortBy}>
                                            {Object.keys(sortings).map((sortKey) => (
                                                <SingleSelect.Option key={sortKey} value={sortKey}>
                                                    {sortings[sortKey]}
                                                </SingleSelect.Option>
                                            ))}
                                        </SingleSelect>
                                    </div>
                                    <div className={filterOverlayStyles.sortOrder}>
                                        <SingleSelect onChange={this.handleSortOrderChange} value={this.sortOrder}>
                                            <SingleSelect.Option value="asc">
                                                {translate('sulu_admin.ascending')}
                                            </SingleSelect.Option>
                                            <SingleSelect.Option value="desc">
                                                {translate('sulu_admin.descending')}
                                            </SingleSelect.Option>
                                        </SingleSelect>
                                    </div>
                                </div>
                            </section>
                        }

                        {sections.includes('presentation') &&
                            <section className={filterOverlayStyles.section}>
                                <h3>{translate('sulu_admin.present_as')}</h3>
                                <div className={filterOverlayStyles.presentation}>
                                    <SingleSelect onChange={this.handlePresentationChange} value={this.presentation}>
                                        {Object.keys(presentations).map((presentationKey) => (
                                            <SingleSelect.Option key={presentationKey} value={presentationKey}>
                                                {presentations[presentationKey]}
                                            </SingleSelect.Option>
                                        ))}
                                    </SingleSelect>
                                </div>
                            </section>
                        }

                        {sections.includes('limit') &&
                            <section className={filterOverlayStyles.section}>
                                <h3>{translate('sulu_admin.limit_result_to')}</h3>
                                <div className={filterOverlayStyles.limit}>
                                    <Number onChange={this.handleLimitChange} value={this.limit} />
                                </div>
                            </section>
                        }
                    </div>
                </Overlay>
                {!smartContentStore.loading && dataSourceAdapter && dataSourceResourceKey && dataSourceDatagridKey &&
                    <SingleDatagridOverlay
                        adapter={dataSourceAdapter}
                        clearSelectionOnClose={false}
                        // TODO make optional
                        datagridKey={dataSourceDatagridKey}
                        locale={smartContentStore.locale}
                        onClose={this.handleCloseDataSourceDialog}
                        onConfirm={this.handleConfirmDataSourceDialog}
                        open={this.showDataSourceDialog}
                        overlayType="dialog"
                        preSelectedItem={this.dataSource}
                        resourceKey={dataSourceResourceKey}
                        title={translate('sulu_admin.choose_data_source')}
                    />
                }
                {!smartContentStore.loading &&
                    <MultiDatagridOverlay
                        adapter="tree_table"
                        clearSelectionOnClose={false}
                        datagridKey="categories"
                        locale={smartContentStore.locale}
                        onClose={this.handleCloseCategoryDialog}
                        onConfirm={this.handleConfirmCategoryDialog}
                        open={this.showCategoryDialog}
                        overlayType="dialog"
                        preSelectedItems={this.categories || []}
                        resourceKey="categories"
                        title={translate('sulu_admin.choose_categories')}
                    />
                }
            </Fragment>
        );
    }
}
