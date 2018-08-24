// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import MultiItemSelection from '../../components/MultiItemSelection';
import {translate} from '../../utils/Translator';
import SmartContentStore from './stores/SmartContentStore';
import FilterOverlay from './FilterOverlay';

type Props = {
    fieldLabel: string,
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
        const {fieldLabel, store} = this.props;

        return (
            <Fragment>
                <MultiItemSelection
                    leftButton={{
                        icon: 'fa-filter',
                        onClick: this.handleFilterClick,
                    }}
                />
                <FilterOverlay
                    dataSourceAdapter="column_list"
                    dataSourceResourceKey={store.dataSourceResourceKey}
                    onClose={this.handleFilterOverlayClose}
                    open={this.showFilterOverlay}
                    // TODO use correct presentations and sortings
                    presentations={{
                        small: 'Klein',
                        large: 'Groß',
                    }}
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
