// @flow
import {action, autorun, observable, computed} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import {Datagrid, DatagridStore, withToolbar} from 'sulu-admin-bundle/containers';
import {Loader} from 'sulu-admin-bundle/components';
import {userStore} from 'sulu-admin-bundle/stores';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import WebspaceSelect from '../../components/WebspaceSelect';
import WebspaceStore from '../../stores/WebspaceStore';
import type {Webspace, Localization} from '../../stores/WebspaceStore/types';
import webspaceOverviewStyles from './webspaceOverview.scss';

const USER_SETTING_WEBSPACE = 'sulu_content.webspace_overview.webspace';

@observer
class WebspaceOverview extends React.Component<ViewProps> {
    page: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    webspace: IObservableValue<string> = observable.box();
    datagridStore: DatagridStore;
    @observable webspaces: Array<Webspace>;
    webspaceDisposer: () => void;

    static getDerivedRouteAttributes() {
        return {
            webspace: userStore.getPersistentSetting(USER_SETTING_WEBSPACE),
        };
    }

    @action handleWebspaceChange = (value: string) => {
        this.webspace.set(value);
        this.setDefaultLocaleForWebspace();
    };

    @action setDefaultLocaleForWebspace = () => {
        const selectedWebspace = this.selectedWebspace;

        if (!selectedWebspace || !selectedWebspace.localizations) {
            return;
        }

        const locale = this.findDefaultLocale(selectedWebspace.localizations);

        if (!locale) {
            throw new Error(
                'Default locale in webspace "' + selectedWebspace.key + '" not found'
            );
        }

        this.locale.set(locale);
    };

    findDefaultLocale = (localizations: Array<Localization>): ?string => {
        for (const localization of localizations) {
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
    };

    @computed get selectedWebspace(): ?Webspace {
        if (!this.webspaces || !this.webspace.get()) {
            return null;
        }

        return this.webspaces.find((webspace) => {
            return webspace.key === this.webspace.get();
        });
    }

    @action componentDidMount() {
        this.webspaceDisposer = autorun(() => {
            userStore.setPersistentSetting(USER_SETTING_WEBSPACE, this.webspace.get());
        });

        const router = this.props.router;
        const observableOptions = {};
        const apiOptions = {};

        router.bind('page', this.page, '1');
        observableOptions.page = this.page;

        router.bind('locale', this.locale);
        observableOptions.locale = this.locale;

        router.bind('webspace', this.webspace);
        apiOptions.webspace = this.webspace;

        this.datagridStore = new DatagridStore('pages', observableOptions, apiOptions);
        WebspaceStore.loadWebspaces()
            .then(action((webspaces) => {
                this.webspaces = webspaces;
            }));
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
        this.webspaceDisposer();
    }

    handleEditClick = (id: string | number) => {
        const {router} = this.props;
        router.navigate(
            'sulu_content.page_edit_form.detail',
            {
                id,
                locale: this.locale.get(),
                webspace: router.attributes.webspace,
            }
        );
    };

    handleAddClick = (id: ?string | number) => {
        const {router} = this.props;
        router.navigate(
            'sulu_content.page_add_form.detail',
            {
                parentId: id,
                locale: this.locale.get(),
                webspace: router.attributes.webspace,
            }
        );
    };

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
                            adapters={['tree_list', 'column_list']}
                            onItemClick={this.handleEditClick}
                            onAddClick={this.handleAddClick}
                            onItemClick={this.handleEditClick}
                            selectable={false}
                            store={this.datagridStore}
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
