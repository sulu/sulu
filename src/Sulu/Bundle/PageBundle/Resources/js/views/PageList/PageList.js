// @flow
import {action, intercept, observable} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import {Loader, Icon} from 'sulu-admin-bundle/components';
import {formMetadataStore, List, ListStore, withToolbar} from 'sulu-admin-bundle/containers';
import userStore from 'sulu-admin-bundle/stores/userStore/userStore';
import {translate} from 'sulu-admin-bundle/utils';
import {CacheClearToolbarAction} from 'sulu-website-bundle/containers';
import {Route} from 'sulu-admin-bundle/services';
import pageListStyles from './pageList.scss';
import type {Localization} from 'sulu-admin-bundle/stores';
import type {ViewProps} from 'sulu-admin-bundle/containers';
import type {AttributeMap} from 'sulu-admin-bundle/services';
import type {Webspace} from '../../stores/webspaceStore/types';
import type {IObservableValue} from 'mobx/lib/mobx';

const USER_SETTINGS_KEY = 'page_list';
const PAGES_RESOURCE_KEY = 'pages';

function getUserSettingsKeyForWebspace(webspace: string) {
    return [USER_SETTINGS_KEY, webspace].join('_');
}

type Props = {
    ...ViewProps,
    webspace: Webspace,
    webspaceKey: IObservableValue<string>,
};

@observer
class PageList extends React.Component<Props> {
    page: IObservableValue<number> = observable.box();
    locale: IObservableValue<string> = observable.box();
    excludeGhostsAndShadows: IObservableValue<boolean> = observable.box(false);
    cacheClearToolbarAction: CacheClearToolbarAction;
    listStore: ListStore;
    excludeGhostsAndShadowsDisposer: () => void;
    webspaceKeyDisposer: () => void;
    @observable availablePageTypes: Array<string> = [];
    @observable availablePageTypesLoading: boolean = true;
    @observable errors = [];

    static getDerivedRouteAttributes(route: Route, attributes: AttributeMap) {
        if (typeof attributes.webspace !== 'string') {
            throw new Error('The "webspace" router attribute must be a string!');
        }

        return {
            active: ListStore.getActiveSetting(PAGES_RESOURCE_KEY, getUserSettingsKeyForWebspace(attributes.webspace)),
        };
    }

    @action redirectToWebspaceLocale = () => {
        const {webspace, router} = this.props;

        if (!webspace || !webspace.localizations) {
            return;
        }

        if (webspace.allLocalizations.find((localization) => localization.localization === this.locale.get())) {
            return;
        }

        const locale = webspace.allLocalizations.find(
            (localization) => localization.localization === userStore.contentLocale
        ) ? userStore.contentLocale : this.findDefaultLocale(webspace.localizations);

        if (!locale) {
            throw new Error(
                'Default locale in webspace "' + webspace.key + '" not found'
            );
        }

        if (locale === this.locale.get()) {
            return;
        }

        router.redirect(router.route.name, {...router.attributes, locale});
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

        if (typeof webspace !== 'string') {
            throw new Error('The "webspace" router attribute must be a string!');
        }

        const observableOptions = {};
        const requestParameters = {webspace};

        this.redirectToWebspaceLocale();
        router.bind('locale', this.locale);

        router.bind('page', this.page, 1);
        observableOptions.page = this.page;

        router.bind('excludeGhostsAndShadows', this.excludeGhostsAndShadows, false);
        observableOptions['exclude-ghosts'] = this.excludeGhostsAndShadows;
        observableOptions['exclude-shadows'] = this.excludeGhostsAndShadows;

        observableOptions.locale = this.locale;

        this.cacheClearToolbarAction = new CacheClearToolbarAction(webspace);

        this.listStore = new ListStore(
            PAGES_RESOURCE_KEY,
            PAGES_RESOURCE_KEY,
            getUserSettingsKeyForWebspace(webspace),
            observableOptions,
            requestParameters
        );
        router.bind('active', this.listStore.active);

        formMetadataStore.getSchemaTypes('page', {webspace, onlyKeys: true}).then(action((schemaTypes: Object) => {
            this.availablePageTypes = Object.keys(schemaTypes.types);
            this.availablePageTypesLoading = false;
        }));

        this.excludeGhostsAndShadowsDisposer = intercept(this.excludeGhostsAndShadows, '', (change) => {
            this.listStore.clear();
            return change;
        });

        this.webspaceKeyDisposer = intercept(webspaceKey, '', (change) => {
            this.listStore.destroy();
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

    handleCopyFinished = (response: Object) => {
        const {webspaceKey} = this.props;
        if (webspaceKey.get() !== response.webspace) {
            webspaceKey.set(response.webspace);
        }
    };

    getIndicators = (item: Object) => {
        const indicators = [];

        if (!this.availablePageTypes.includes(item.template)) {
            indicators.push(<Icon key="missing-template" name="su-exclamation-circle" />);
        }

        return indicators;
    };

    @action handleDeleteError = (error?: Object): void => {
        const message = error?.detail || error?.title || translate('sulu_admin.unexpected_delete_server_error');

        this.errors.push(message);
    };

    render() {
        const {getIndicators} = this;

        return (
            <div className={pageListStyles.pageList}>
                {this.availablePageTypesLoading
                    ? <Loader />
                    : <List
                        adapterOptions={{
                            column_list: {
                                display_root_level_toolbar: false,
                                get_indicators: getIndicators,
                            },
                        }}
                        adapters={['column_list', 'tree_table']}
                        onCopyFinished={this.handleCopyFinished}
                        onDeleteError={this.handleDeleteError}
                        onItemAdd={this.handleItemAdd}
                        onItemClick={this.handleEditClick}
                        searchable={false}
                        selectable={false}
                        store={this.listStore}
                        toolbarClassName={pageListStyles.listToolbar}
                    />
                }
                {this.cacheClearToolbarAction.getNode()}
            </div>
        );
    }
}

const PageListWithToolbar = withToolbar(PageList, function() {
    const {webspace} = this.props;

    if (!webspace) {
        return {};
    }

    return {
        errors: this.errors,
        items: [
            {
                label: translate('sulu_page.show_ghost_and_shadow'),
                onClick: action(() => {
                    this.excludeGhostsAndShadows.set(!this.excludeGhostsAndShadows.get());
                }),
                type: 'toggler',
                value: !this.excludeGhostsAndShadows.get(),
            },
            this.cacheClearToolbarAction.getToolbarItemConfig(),
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

export default PageListWithToolbar;
