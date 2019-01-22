// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import MultiItemSelection from '../../components/MultiItemSelection';
import {translate} from '../../utils/Translator';
import smartContentConfigStore from './stores/SmartContentConfigStore';
import SmartContentStore from './stores/SmartContentStore';
import FilterOverlay from './FilterOverlay';
import SmartContentItem from './SmartContentItem';
import type {Presentation, SmartContentConfig} from './types';

type Props = {|
    disabled: boolean,
    fieldLabel: string,
    presentations: Array<Presentation>,
    store: SmartContentStore,
|};

@observer
export default class SmartContent extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        presentations: [],
    };

    config: SmartContentConfig;
    sections: Array<string> = [];
    sortings: {[key: string]: string};

    @observable showFilterOverlay = false;

    constructor(props: Props) {
        super(props);
        this.initialize();
    }

    @action initialize() {
        const {store} = this.props;

        this.config = smartContentConfigStore.getConfig(store.provider);

        if (this.config.datasourceResourceKey && this.config.datasourceAdapter) {
            this.sections.push('datasource');
            if (store.includeSubElements === undefined) {
                store.includeSubElements = false;
            }
        }

        if (this.config.categories) {
            this.sections.push('categories');
            if (store.categories === undefined) {
                store.categoryOperator = 'or';
            }
        }

        if (this.config.tags) {
            this.sections.push('tags');
            if (store.tags === undefined) {
                store.tagOperator = 'or';
            }
        }

        if (this.config.audienceTargeting) {
            this.sections.push('audienceTargeting');
            if (store.audienceTargeting === undefined) {
                store.audienceTargeting = false;
            }
        }

        if (this.config.sorting.length > 0) {
            this.sections.push('sorting');
            if (store.sortBy === undefined) {
                store.sortBy = this.config.sorting[0].name;
            }
            if (store.sortOrder === undefined) {
                store.sortOrder = 'asc';
            }
        }

        if (this.config.presentAs && this.props.presentations.length > 0) {
            this.sections.push('presentation');
            if (store.presentation === undefined) {
                store.presentation = this.props.presentations[0].name;
            }
        }

        if (this.config.limit) {
            this.sections.push('limit');
        }

        this.sortings = this.config.sorting.reduce((sortings, sorting) => {
            sortings[sorting.name] = translate(sorting.value);
            return sortings;
        }, {});
    }

    @action handleFilterClick = () => {
        this.showFilterOverlay = true;
    };

    @action handleFilterOverlayClose = () => {
        this.showFilterOverlay = false;
    };

    render() {
        const {disabled, fieldLabel, store} = this.props;

        const presentations = this.props.presentations.reduce((presentations, presentation) => {
            presentations[presentation.name] = presentation.value;
            return presentations;
        }, {});

        return (
            <Fragment>
                <MultiItemSelection
                    disabled={disabled}
                    label={translate('sulu_admin.smart_content_label', {count: store.items.length})}
                    leftButton={{
                        icon: 'fa-filter',
                        onClick: this.handleFilterClick,
                    }}
                    loading={store.itemsLoading || store.loading}
                    sortable={false}
                >
                    {store.items.map((item, index) => (
                        <MultiItemSelection.Item id={item.id} index={index + 1} key={index}>
                            <SmartContentItem item={item} />
                        </MultiItemSelection.Item>
                    ))}
                </MultiItemSelection>
                <FilterOverlay
                    dataSourceAdapter={this.config.datasourceAdapter}
                    // TODO make optional
                    dataSourceDatagridKey={this.config.datasourceDatagridKey}
                    dataSourceResourceKey={this.config.datasourceResourceKey}
                    onClose={this.handleFilterOverlayClose}
                    open={this.showFilterOverlay}
                    presentations={presentations}
                    sections={this.sections}
                    smartContentStore={store}
                    sortings={this.sortings}
                    title={translate('sulu_admin.filter_overlay_title', {fieldLabel})}
                />
            </Fragment>
        );
    }
}
