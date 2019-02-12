// @flow
import React, {Fragment} from 'react';
import {autorun, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import Loader from '../../components/Loader';
import Tabs from '../../components/Tabs';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../utils/Translator';
import ResourceStore from '../../stores/ResourceStore';
import resourceTabsStyles from './resourceTabs.scss';

type Props = ViewProps & {
    locales?: Array<string>,
    titleProperty?: string,
};

@observer
export default class ResourceTabs extends React.Component<Props> {
    resourceStore: ResourceStore;
    redirectToRouteWithHighestPriorityDisposer: () => void;

    constructor(props: Props) {
        super(props);

        const {router, route} = this.props;
        const {
            attributes: {
                id,
            },
        } = router;
        const {
            options: {
                resourceKey,
            },
        } = route;

        if (!resourceKey) {
            throw new Error('The route does not define the mandatory "resourceKey" option');
        }

        const options = {};
        if (this.locales) {
            options.locale = observable.box();
            router.bind('locale', options.locale);
        }

        this.resourceStore = new ResourceStore(resourceKey, id, options);

        this.redirectToRouteWithHighestPriorityDisposer = autorun(this.redirectToRouteWithHighestPriority);
    }

    componentWillUnmount() {
        this.redirectToRouteWithHighestPriorityDisposer();
        this.resourceStore.destroy();
    }

    @computed get locales() {
        const {
            locales: propsLocales,
            route: {
                options: {
                    locales: routeLocales,
                },
            },
        } = this.props;

        return routeLocales ? routeLocales : propsLocales;
    }

    @computed get title() {
        const {
            route: {
                options: {
                    titleProperty: routeTitleProperty,
                },
            },
            titleProperty,
        } = this.props;

        return this.resourceStore.data[titleProperty || routeTitleProperty];
    }

    @computed get visibleTabRoutes(): Array<Object> {
        const {route} = this.props;
        const data = toJS(this.resourceStore.data);

        return route.children
            .filter((childRoute) => {
                const {
                    options: {
                        tabCondition,
                    },
                } = childRoute;

                return !tabCondition || jexl.evalSync(tabCondition, data);
            })
            .sort((childRoute1, childRoute2) => {
                const {tabOrder: tabOrder1 = 0} = childRoute1.options;
                const {tabOrder: tabOrder2 = 0} = childRoute2.options;

                return tabOrder1 - tabOrder2;
            });
    }

    @computed get visibleTabRouteWithHighestPriority(): Object {
        return this.visibleTabRoutes.reduce((prioritizedRoute, route) => {
            if (!prioritizedRoute) {
                return route;
            }

            const {
                options: {
                    tabPriority: highestTabPriority = 0,
                },
            } = prioritizedRoute;

            const {
                options: {
                    tabPriority = 0,
                },
            } = route;

            if (highestTabPriority >= tabPriority) {
                return prioritizedRoute;
            }

            return route;
        }, undefined);
    }

    redirectToRouteWithHighestPriority = (): void => {
        const {route, router} = this.props;

        if (!this.resourceStore.initialized || this.resourceStore.loading) {
            return;
        }

        if (!route.children.includes(router.route) && router.route !== route) {
            return;
        }

        if (this.visibleTabRoutes.includes(router.route)) {
            return;
        }

        if (!this.visibleTabRouteWithHighestPriority) {
            return;
        }

        router.redirect(this.visibleTabRouteWithHighestPriority.name, router.attributes);
    };

    handleSelect = (index: number) => {
        const {router} = this.props;
        router.navigate(this.visibleTabRoutes[index].name, router.attributes);

        // TODO replace by asking for confirmation when changing tabs and reload only if dirty data should be deleted
        this.resourceStore.load();
    };

    render() {
        const {children, route} = this.props;

        const ChildComponent = children ? children({locales: this.locales, resourceStore: this.resourceStore}) : null;

        const selectedRouteIndex = ChildComponent
            ? this.visibleTabRoutes.findIndex((childRoute) => childRoute === ChildComponent.props.route)
            : undefined;

        const selectedRoute = selectedRouteIndex !== undefined ? this.visibleTabRoutes[selectedRouteIndex] : undefined;

        return this.resourceStore.initialized
            ? (
                <Fragment>
                    <div className={resourceTabsStyles.tabsContainer}>
                        <Tabs onSelect={this.handleSelect} selectedIndex={selectedRouteIndex}>
                            {this.visibleTabRoutes.map((tabRoute) => {
                                const tabTitle = tabRoute.options.tabTitle;
                                return (
                                    <Tabs.Tab key={tabRoute.name}>
                                        {tabTitle ? translate(tabTitle) : tabRoute.name}
                                    </Tabs.Tab>
                                );
                            })}
                        </Tabs>
                    </div>
                    {route.children[0] !== selectedRoute && this.title &&
                        <h1>{this.title}</h1>
                    }
                    {ChildComponent}
                </Fragment>
            )
            : (
                <div className={resourceTabsStyles.loader}>
                    <Loader />
                </div>
            );
    }
}
