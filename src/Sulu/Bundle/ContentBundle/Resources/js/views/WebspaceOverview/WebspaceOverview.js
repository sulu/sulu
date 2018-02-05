// @flow
import {action, observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import React from 'react';
import {Datagrid, DatagridStore, withToolbar} from 'sulu-admin-bundle/containers';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import WebspaceSelect from '../../components/WebspaceSelect';
import webspaceOverviewStyles from './webspaceOverview.scss';

// TODO: After implemented loading of webspace data from server, move or delete this also!
type Webspace = {
    key: string,
    name: string,
};

@observer
class WebspaceOverview extends React.Component<ViewProps> {
    page: IObservableValue<number> = observable();
    locale: IObservableValue<string> = observable();
    webspace: IObservableValue<string> = observable();
    datagridStore: DatagridStore;

    @action handleWebspaceChange = (value: string) => {
        this.webspace.set(value);
    };

    componentWillMount() {
        const router = this.props.router;
        const observableOptions = {};
        const apiOptions = {};

        router.bind('page', this.page, '1');
        observableOptions.page = this.page;

        router.bind('locale', this.locale);
        observableOptions.locale = this.locale;

        router.bind('webspace', this.webspace);
        apiOptions.webspace = this.webspace;

        this.datagridStore = new DatagridStore('nodes', observableOptions, apiOptions);
    }

    componentWillUnmount() {
        const {router} = this.props;

        router.unbind('page', this.page);
        router.unbind('webspace', this.webspace);
        router.unbind('locale', this.locale);

        this.datagridStore.destroy();
    }

    render() {
        // TODO: Load this data dynamically from server!
        const webspaces: Array<Webspace> = [
            {key: 'sulu', name: 'Sulu'},
            {key: 'sulu_blog', name: 'Sulu Blog'},
            {key: 'sulu_doc', name: 'Sulu Doc'},
        ];

        return (
            <div className={webspaceOverviewStyles.pageList}>
                <div className={webspaceOverviewStyles.webspaceSelect}>
                    <WebspaceSelect value={this.webspace.get()} onChange={this.handleWebspaceChange}>
                        {webspaces.map((webspace) => (
                            <WebspaceSelect.Item key={webspace.key} value={webspace.key}>
                                {webspace.name}
                            </WebspaceSelect.Item>
                        ))}
                    </WebspaceSelect>
                </div>
                <Datagrid
                    className={webspaceOverviewStyles.datagrid}
                    store={this.datagridStore}
                    adapters={['column_list']}
                />
            </div>
        );
    }
}

export default withToolbar(WebspaceOverview, function() {
    // TODO: Load this data dynamically from server!
    const locale = {
        value: this.locale.get(),
        onChange: action((locale) => {
            this.locale.set(locale);
        }),
        options: [{
            value: 'en',
            label: 'en',
        }],
    };

    return {
        locale,
    };
});
