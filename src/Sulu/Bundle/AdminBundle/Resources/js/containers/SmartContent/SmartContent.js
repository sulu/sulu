// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import MultiItemSelection from '../../components/MultiItemSelection';
import {translate} from '../../utils/Translator';
import smartContentConfigStore from './stores/SmartContentConfigStore';
import SmartContentStore from './stores/SmartContentStore';
import FilterOverlay from './FilterOverlay';

type Props = {
    fieldLabel: string,
    provider: string,
    store: SmartContentStore,
};

@observer
export default class SmartContent extends React.Component<Props> {
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
        if (smartContentConfig.sorting) {
            sections.push('sorting');
        }
        if (smartContentConfig.presentAs) {
            sections.push('presentation');
        }
        if (smartContentConfig.limit) {
            sections.push('limit');
        }

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
                    presentations={{
                        small: 'Klein',
                        large: 'Groß',
                    }}
                    sections={sections}
                    sortings={{
                        title: 'Titel',
                        admin: 'Admin-Reihenfolge',
                    }}
                    smartContentStore={store}
                    title={translate('sulu_admin.filter_overlay_title', {fieldLabel})}
                />
            </Fragment>
        );
    }
}
