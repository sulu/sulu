// @flow
import {action, computed, intercept, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import {Datagrid, DatagridStore, withToolbar} from 'sulu-admin-bundle/containers';
import {Dialog, Loader} from 'sulu-admin-bundle/components';
import {userStore} from 'sulu-admin-bundle/stores';
import type {Localization} from 'sulu-admin-bundle/stores';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {Requester} from 'sulu-admin-bundle/services';
import type {AttributeMap, Route} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import WebspaceSelect from '../../components/WebspaceSelect';
import webspaceStore from '../../stores/WebspaceStore';
import type {Webspace} from '../../stores/WebspaceStore/types';
import webspaceOverviewStyles from './webspaceOverview.scss';

const USER_SETTINGS_KEY = 'webspace_overview';

const USER_SETTING_PREFIX = 'sulu_content.webspace_overview';
const USER_SETTING_WEBSPACE = [USER_SETTING_PREFIX, 'webspace'].join('.');

const PAGES_RESOURCE_KEY = 'pages';

function getUserSettingsKeyForWebspace(webspace: string) {
    return [USER_SETTINGS_KEY, webspace].join('_');
}

@observer
class WebspaceOverview extends React.Component<ViewProps> {
    static clearCacheEndpoint: string;

    page: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    webspace: IObservableValue<string> = observable.box();
    excludeGhostsAndShadows: IObservableValue<boolean> = observable.box(false);
    datagridStore: DatagridStore;
    @observable webspaces: Array<Webspace>;
    @observable showCacheClearDialog: boolean = false;
    @observable cacheClearing: boolean = false;
    excludeGhostsAndShadowsDisposer: () => void;
    webspaceDisposer: () => void;

    static getDerivedRouteAttributes(route: Route, attributes: AttributeMap) {
        const webspace = attributes.webspace
            ? attributes.webspace
            : userStore.getPersistentSetting(USER_SETTING_WEBSPACE);

        return {
            active: DatagridStore.getActiveSetting(PAGES_RESOURCE_KEY, getUserSettingsKeyForWebspace(webspace)),
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

        this.datagridStore = new DatagridStore(
            PAGES_RESOURCE_KEY,
            getUserSettingsKeyForWebspace(this.webspace.get()),
            observableOptions,
            apiOptions
        );
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

        webspaceStore.loadWebspaces()
            .then(action((webspaces) => {
                this.webspaces = webspaces;
            }));
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
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

    @action handleCacheClearCancel = () => {
        this.showCacheClearDialog = false;
    };

    @action handleCacheClearConfirm = () => {
        this.cacheClearing = true;
        Requester.delete(WebspaceOverviewWithToolbar.clearCacheEndpoint).then(action(() => {
            this.showCacheClearDialog = false;
            this.cacheClearing = false;
        }));
    };

    render() {
        return (
            <div className={webspaceOverviewStyles.webspaceOverview}>
                {this.webspaces
                    ? <Datagrid
                        adapters={['column_list', 'tree_table']}
                        header={this.webspace &&
                            <WebspaceSelect onChange={this.handleWebspaceChange} value={this.webspace.get()}>
                                {this.webspaces.map((webspace) => (
                                    <WebspaceSelect.Item key={webspace.key} value={webspace.key}>
                                        {webspace.name}
                                    </WebspaceSelect.Item>
                                ))}
                            </WebspaceSelect>
                        }
                        onItemAdd={this.handleItemAdd}
                        onItemClick={this.handleEditClick}
                        searchable={false}
                        selectable={false}
                        store={this.datagridStore}
                    />
                    : <div>
                        <Loader />
                    </div>
                }
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.cacheClearing}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleCacheClearCancel}
                    onConfirm={this.handleCacheClearConfirm}
                    open={this.showCacheClearDialog}
                    title={translate('sulu_content.cache_clear_warning_title')}
                >
                    {translate('sulu_content.cache_clear_warning_text')}
                </Dialog>
            </div>
        );
    }
}

const WebspaceOverviewWithToolbar = withToolbar(WebspaceOverview, function() {
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
            {
                icon: 'su-paint',
                label: translate('sulu_content.cache_clear'),
                onClick: action(() => {
                    this.showCacheClearDialog = true;
                }),
                type: 'button',
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

export default WebspaceOverviewWithToolbar;
