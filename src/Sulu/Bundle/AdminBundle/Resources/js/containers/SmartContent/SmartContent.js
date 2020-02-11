// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import MultiItemSelection from '../../components/MultiItemSelection';
import {translate} from '../../utils/Translator';
import smartContentConfigStore from './stores/smartContentConfigStore';
import SmartContentStore from './stores/SmartContentStore';
import FilterOverlay from './FilterOverlay';
import SmartContentItem from './SmartContentItem';
import type {FilterCriteria, Presentation, SmartContentConfig} from './types';

type Props = {|
    categoryRootKey?: string,
    defaultValue: FilterCriteria,
    disabled: boolean,
    fieldLabel: string,
    presentations: Array<Presentation>,
    store: SmartContentStore,
|};

@observer
class SmartContent extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        presentations: [],
    };

    config: SmartContentConfig;
    sections: Array<string> = [];

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
        }

        if (this.config.categories) {
            this.sections.push('categories');
        }

        if (this.config.tags) {
            this.sections.push('tags');
        }

        if (this.config.audienceTargeting) {
            this.sections.push('audienceTargeting');
        }

        if (this.config.sorting.length > 0) {
            this.sections.push('sorting');
        }

        if (this.config.presentAs && this.props.presentations.length > 0) {
            this.sections.push('presentation');
        }

        if (this.config.limit) {
            this.sections.push('limit');
        }
    }

    @action handleFilterClick = () => {
        this.showFilterOverlay = true;
    };

    @action handleFilterOverlayClose = () => {
        this.showFilterOverlay = false;
    };

    render() {
        const {categoryRootKey, defaultValue, disabled, fieldLabel, store} = this.props;

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
                    categoryRootKey={categoryRootKey}
                    dataSourceAdapter={this.config.datasourceAdapter}
                    dataSourceListKey={this.config.datasourceListKey}
                    dataSourceResourceKey={this.config.datasourceResourceKey}
                    defaultValue={defaultValue}
                    onClose={this.handleFilterOverlayClose}
                    open={this.showFilterOverlay}
                    presentations={presentations}
                    sections={this.sections}
                    smartContentStore={store}
                    sortings={this.config.sorting}
                    title={translate('sulu_admin.filter_overlay_title', {fieldLabel})}
                />
            </Fragment>
        );
    }
}

export default SmartContent;
