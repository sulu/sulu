// @flow
import {action, intercept, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import {List, ListStore, withToolbar} from 'sulu-admin-bundle/containers';
import {Dialog} from 'sulu-admin-bundle/components';
import type {Localization} from 'sulu-admin-bundle/stores';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import {Requester} from 'sulu-admin-bundle/services';
import type {AttributeMap, Route} from 'sulu-admin-bundle/services';
import {translate} from 'sulu-admin-bundle/utils';
import type {Webspace} from '../../stores/WebspaceStore/types';
import webspaceOverviewStyles from './webspaceOverview.scss';

const USER_SETTINGS_KEY = 'webspace_overview';
const PAGES_RESOURCE_KEY = 'pages';

function getUserSettingsKeyForWebspace(webspace: string) {
    return [USER_SETTINGS_KEY, webspace].join('_');
}

type Props = ViewProps & {
    webspace: Webspace,
    webspaceKey: IObservableValue<string>,
};

@observer
class WebspaceOverview extends React.Component<Props> {
    static clearCacheEndpoint: string;

    page: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    excludeGhostsAndShadows: IObservableValue<boolean> = observable.box(false);
    listStore: ListStore;
    @observable showCacheClearDialog: boolean = false;
    @observable cacheClearing: boolean = false;
    excludeGhostsAndShadowsDisposer: () => void;
    webspaceKeyDisposer: () => void;

    static getDerivedRouteAttributes(route: Route, attributes: AttributeMap) {
        return {
            active: ListStore.getActiveSetting(PAGES_RESOURCE_KEY, getUserSettingsKeyForWebspace(attributes.webspace)),
        };
    }

    @action setDefaultLocaleForWebspace = () => {
        const {webspace} = this.props;

        if (!webspace || !webspace.localizations) {
            return;
        }

        if (webspace.allLocalizations.find((localization) => localization.localization === this.locale.get())) {
            return;
        }

        const locale = this.findDefaultLocale(webspace.localizations);

        if (!locale) {
            throw new Error(
                'Default locale in webspace "' + webspace.key + '" not found'
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

    constructor(props: Props) {
        super(props);

        const {router, webspaceKey} = this.props;

        const {
            attributes: {
                webspace,
            },
        } = router;

        const observableOptions = {};
        const apiOptions = {webspace};

        router.bind('page', this.page, 1);
        observableOptions.page = this.page;

        router.bind('excludeGhostsAndShadows', this.excludeGhostsAndShadows, false);
        observableOptions['exclude-ghosts'] = this.excludeGhostsAndShadows;
        observableOptions['exclude-shadows'] = this.excludeGhostsAndShadows;

        router.bind('locale', this.locale);

        this.setDefaultLocaleForWebspace();
        observableOptions.locale = this.locale;

        this.listStore = new ListStore(
            PAGES_RESOURCE_KEY,
            PAGES_RESOURCE_KEY,
            getUserSettingsKeyForWebspace(webspace),
            observableOptions,
            apiOptions
        );
        router.bind('active', this.listStore.active);

        this.excludeGhostsAndShadowsDisposer = intercept(this.excludeGhostsAndShadows, '', (change) => {
            this.listStore.clear();
            return change;
        });

        this.webspaceKeyDisposer = intercept(webspaceKey, '', (change) => {
            this.listStore.active.set(undefined);
            return change;
        });
    }

    componentWillUnmount() {
        this.webspaceKeyDisposer();
        this.listStore.destroy();
        this.excludeGhostsAndShadowsDisposer();
    }

    handleEditClick = (id: string | number) => {
        const {router} = this.props;
        router.navigate(
            'sulu_page.page_edit_form',
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
            'sulu_page.page_add_form',
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
                <List
                    adapters={['column_list', 'tree_table']}
                    onItemAdd={this.handleItemAdd}
                    onItemClick={this.handleEditClick}
                    searchable={false}
                    selectable={false}
                    store={this.listStore}
                />
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmLoading={this.cacheClearing}
                    confirmText={translate('sulu_admin.ok')}
                    onCancel={this.handleCacheClearCancel}
                    onConfirm={this.handleCacheClearConfirm}
                    open={this.showCacheClearDialog}
                    title={translate('sulu_page.cache_clear_warning_title')}
                >
                    {translate('sulu_page.cache_clear_warning_text')}
                </Dialog>
            </div>
        );
    }
}

const WebspaceOverviewWithToolbar = withToolbar(WebspaceOverview, function() {
    const {webspace} = this.props;

    if (!webspace) {
        return {};
    }

    return {
        items: [
            {
                label: translate('sulu_page.show_ghost_and_shadow'),
                onClick: action(() => {
                    this.excludeGhostsAndShadows.set(!this.excludeGhostsAndShadows.get());
                }),
                type: 'toggler',
                value: !this.excludeGhostsAndShadows.get(),
            },
            {
                icon: 'su-paint',
                label: translate('sulu_page.cache_clear'),
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
            options: webspace.allLocalizations.map((localization) => ({
                value: localization.localization,
                label: localization.name,
            })),
        },
    };
});

export default WebspaceOverviewWithToolbar;
