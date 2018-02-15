// @flow
import {action, observable, computed} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import {Datagrid, DatagridStore, withToolbar} from 'sulu-admin-bundle/containers';
import {Loader} from 'sulu-admin-bundle/components';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import WebspaceSelect from '../../components/WebspaceSelect';
import WebspaceStore from '../../stores/WebspaceStore';
import type {Webspace, Localization} from '../../stores/WebspaceStore/types';
import webspaceOverviewStyles from './webspaceOverview.scss';

@observer
class WebspaceOverview extends React.Component<ViewProps> {
    page: IObservableValue<number> = observable();
    locale: IObservableValue<string> = observable();
    webspace: IObservableValue<string> = observable();
    datagridStore: DatagridStore;
    @observable webspaces: Array<Webspace>;

    @action handleWebspaceChange = (value: string) => {
        this.webspace.set(value);
        this.setDefaultLocaleForWebspace();
    };

    @action setDefaultLocaleForWebspace = () => {
        if (!this.selectedWebspace || !this.selectedWebspace.localizations) {
            return;
        }

        this.locale.set(this.findDefaultLocale(this.selectedWebspace.localizations));
    };

    findDefaultLocale = (localizations: Array<Localization>): string => {
        for (let localization of localizations) {
            if (localization.default) {
                return localization.locale;
            }

            if (localization.children) {
                const locale = this.findDefaultLocale(localization.children);

                if (locale) {
                    return locale;
                }
            }
        }

        throw new Error('Default locale in webspace not found');
    };

    @computed get selectedWebspace(): ?Webspace {
        if (!this.webspaces || !this.webspace.get()) {
            return null;
        }

        return this.webspaces.find((webspace) => {
            return webspace.key === this.webspace.get();
        });
    }

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
        WebspaceStore.loadWebspaces()
            .then(action((webspaces) => {
                this.webspaces = webspaces;
            }));
    }

    componentWillUnmount() {
        const {router} = this.props;

        router.unbind('page', this.page);
        router.unbind('webspace', this.webspace);
        router.unbind('locale', this.locale);

        this.datagridStore.destroy();
    }

    render() {
        return (
            <div className={webspaceOverviewStyles.webspaceOverview}>
                {!this.webspaces &&
                    <div>
                        <Loader />
                    </div>
                }

                {!!this.webspaces &&
                    <Fragment>
                        <div className={webspaceOverviewStyles.webspaceSelect}>
                            <WebspaceSelect value={this.webspace.get()} onChange={this.handleWebspaceChange}>
                                {this.webspaces.map((webspace) => (
                                    <WebspaceSelect.Item key={webspace.key} value={webspace.key}>
                                        {webspace.name}
                                    </WebspaceSelect.Item>
                                ))}
                            </WebspaceSelect>
                        </div>
                        <Datagrid
                            store={this.datagridStore}
                            adapters={['column_list']}
                        />
                    </Fragment>
                }
            </div>
        );
    }
}

export default withToolbar(WebspaceOverview, function() {
    if (!this.selectedWebspace) {
        return {};
    }

    const options = this.selectedWebspace.allLocalizations.map((localization) => ({
        value: localization.localization,
        label: localization.name,
    }));

    const locale = {
        value: this.locale.get(),
        onChange: action((locale) => {
            this.locale.set(locale);
        }),
        options,
    };

    return {
        locale,
    };
});
