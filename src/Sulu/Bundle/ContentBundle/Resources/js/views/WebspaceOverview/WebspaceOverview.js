// @flow
import {action, computed, intercept, observable, reaction} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import {observer} from 'mobx-react';
import React from 'react';
import {Datagrid, DatagridStore, withToolbar} from 'sulu-admin-bundle/containers';
import {Loader} from 'sulu-admin-bundle/components';
import {userStore} from 'sulu-admin-bundle/stores';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {translate} from 'sulu-admin-bundle/utils';
import WebspaceSelect from '../../components/WebspaceSelect';
import WebspaceStore from '../../stores/WebspaceStore';
import type {Webspace, Localization} from '../../stores/WebspaceStore/types';
import webspaceOverviewStyles from './webspaceOverview.scss';

const USER_SETTING_PREFIX = 'sulu_content.webspace_overview';
const USER_SETTING_WEBSPACE = [USER_SETTING_PREFIX, 'webspace'].join('.');

function getWebspaceActiveKey(webspace) {
    return [USER_SETTING_PREFIX, 'webspace', webspace, 'active'].join('.');
}

@observer
class WebspaceOverview extends React.Component<ViewProps> {
    page: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    webspace: IObservableValue<string> = observable.box();
    excludeGhostsAndShadows: IObservableValue<boolean> = observable.box(false);
    datagridStore: DatagridStore;
    @observable webspaces: Array<Webspace>;
    activeDisposer: () => void;
    excludeGhostsAndShadowsDisposer: () => void;
    webspaceDisposer: () => void;

    static getDerivedRouteAttributes() {
        const webspace = userStore.getPersistentSetting(USER_SETTING_WEBSPACE);

        return {
            active: userStore.getPersistentSetting(getWebspaceActiveKey(webspace)),
            webspace,
        };
    }

    @action handleWebspaceChange = (value: string) => {
        this.datagridStore.destroy();
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
        const router = this.props.router;
        const observableOptions = {};
        const apiOptions = {};

        router.bind('page', this.page, 1);
        observableOptions.page = this.page;

        router.bind('excludeGhostsAndShadows', this.excludeGhostsAndShadows, false);
        observableOptions['exclude-ghosts'] = this.excludeGhostsAndShadows;
        observableOptions['exclude-shadows'] = this.excludeGhostsAndShadows;

        router.bind('locale', this.locale);
        observableOptions.locale = this.locale;

        router.bind('webspace', this.webspace);
        apiOptions.webspace = this.webspace;

        this.datagridStore = new DatagridStore('pages', observableOptions, apiOptions);
        router.bind('active', this.datagridStore.active);

        this.excludeGhostsAndShadowsDisposer = intercept(this.excludeGhostsAndShadows, '', (change) => {
            this.datagridStore.clear();
            return change;
        });

        this.webspaceDisposer = intercept(this.webspace, '', (change) => {
            userStore.setPersistentSetting(USER_SETTING_WEBSPACE, change.newValue);
            this.datagridStore.active.set(undefined);
            return change;
        });

        this.activeDisposer = reaction(
            () => this.datagridStore.active.get(),
            (active) => {
                if (!active) {
                    return;
                }

                userStore.setPersistentSetting(
                    getWebspaceActiveKey(this.webspace.get()),
                    active
                );
            }
        );

        WebspaceStore.loadWebspaces()
            .then(action((webspaces) => {
                this.webspaces = webspaces;
            }));
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
        this.activeDisposer();
        this.excludeGhostsAndShadowsDisposer();
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

    handleItemAdd = (id: ?string | number) => {
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
                {this.webspaces
                    ? <Datagrid
                        adapters={['column_list', 'tree_table']}
                        onItemAdd={this.handleItemAdd}
                        onItemClick={this.handleEditClick}
                        header={this.webspace &&
                            <WebspaceSelect value={this.webspace.get()} onChange={this.handleWebspaceChange}>
                                {this.webspaces.map((webspace) => (
                                    <WebspaceSelect.Item key={webspace.key} value={webspace.key}>
                                        {webspace.name}
                                    </WebspaceSelect.Item>
                                ))}
                            </WebspaceSelect>
                        }
                        selectable={false}
                        searchable={false}
                        store={this.datagridStore}
                    />
                    : <div>
                        <Loader />
                    </div>
                }
            </div>
        );
    }
}

export default withToolbar(WebspaceOverview, function() {
    if (!this.selectedWebspace) {
        return {};
    }

    return {
        items: [
            {
                label: translate('sulu_content.show_ghost_and_shadow'),
                onClick: action(() => {
                    this.excludeGhostsAndShadows.set(!this.excludeGhostsAndShadows.get());
                }),
                type: 'toggler',
                value: !this.excludeGhostsAndShadows.get(),
            },
        ],
        locale: {
            value: this.locale.get(),
            onChange: action((locale) => {
                this.locale.set(locale);
            }),
            options: this.selectedWebspace.allLocalizations.map((localization) => ({
                value: localization.localization,
                label: localization.name,
            })),
        },
    };
});
