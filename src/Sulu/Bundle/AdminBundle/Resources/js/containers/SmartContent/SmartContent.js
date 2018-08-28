// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import MultiItemSelection from '../../components/MultiItemSelection';
import {translate} from '../../utils/Translator';
import smartContentConfigStore from './stores/SmartContentConfigStore';
import SmartContentStore from './stores/SmartContentStore';
import FilterOverlay from './FilterOverlay';
import type {Presentation} from './types';

type Props = {
    fieldLabel: string,
    presentations: Array<Presentation>,
    provider: string,
    store: SmartContentStore,
};

@observer
export default class SmartContent extends React.Component<Props> {
    static defaultProps = {
        presentations: [],
    };

    @observable showFilterOverlay = false;

    @action handleFilterClick = () => {
        this.showFilterOverlay = true;
    };

    @action handleFilterOverlayClose = () => {
        this.showFilterOverlay = false;
    };

    render() {
        const {fieldLabel, provider, store} = this.props;
        const smartContentConfig = smartContentConfigStore.getConfig(provider);

        const sections = [];
        if (smartContentConfig.datasourceResourceKey && smartContentConfig.datasourceAdapter) {
            sections.push('datasource');
        }
        if (smartContentConfig.categories) {
            sections.push('categories');
        }
        if (smartContentConfig.tags) {
            sections.push('tags');
        }
        if (smartContentConfig.audienceTargeting) {
            sections.push('audienceTargeting');
        }
        if (smartContentConfig.sorting.length > 0) {
            sections.push('sorting');
        }
        if (smartContentConfig.presentAs && this.props.presentations.length > 0) {
            sections.push('presentation');
        }
        if (smartContentConfig.limit) {
            sections.push('limit');
        }

        const sortings = smartContentConfig.sorting.reduce((sortings, sorting) => {
            sortings[sorting.name] = translate(sorting.value);
            return sortings;
        }, {});

        const presentations = this.props.presentations.reduce((presentations, presentation) => {
            presentations[presentation.name] = presentation.value;
            return presentations;
        }, {});

        return (
            <Fragment>
                <MultiItemSelection
                    leftButton={{
                        icon: 'fa-filter',
                        onClick: this.handleFilterClick,
                    }}
                    loading={store.itemsLoading || store.loading}
                >
                    {store.items.map((item, index) => (
                        <MultiItemSelection.Item key={index} id={item.id} index={index + 1}>
                            {item.title /* TODO Define field via props to read from item */}
                        </MultiItemSelection.Item>
                    ))}
                </MultiItemSelection>
                <FilterOverlay
                    dataSourceAdapter={smartContentConfig.datasourceAdapter}
                    dataSourceResourceKey={smartContentConfig.datasourceResourceKey}
                    onClose={this.handleFilterOverlayClose}
                    open={this.showFilterOverlay}
                    // TODO use correct presentations and sortings
                    presentations={presentations}
                    sections={sections}
                    sortings={sortings}
                    smartContentStore={store}
                    title={translate('sulu_admin.filter_overlay_title', {fieldLabel})}
                />
            </Fragment>
        );
    }
}
