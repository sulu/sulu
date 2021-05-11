// @flow
import React, {Fragment} from 'react';
import type {Element, Node} from 'react';
import {autorun, computed} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import TabsComponent from '../../components/Tabs';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../utils/Translator';
import {Route} from '../../services/Router';
import Badge, {type BadgeOptions} from '../../containers/Badge';
import tabsStyles from './tabs.scss';

type Props<T> = {
    ...ViewProps,
    children?: (T) => Element<*> | null,
    childrenProps: T,
    header?: Node,
    routeChildren?: Array<Route>,
    selectedIndex?: number,
    title?: string,
};

@observer
class Tabs<T> extends React.Component<Props<T>> {
    static defaultProps = {
        childrenProps: {},
    };

    redirectToRouteWithHighestPriorityDisposer: () => void;

    constructor(props: Props<T>) {
        super(props);

        this.redirectToRouteWithHighestPriorityDisposer = autorun(this.redirectToRouteWithHighestPriority);
    }

    componentWillUnmount() {
        this.redirectToRouteWithHighestPriorityDisposer();
    }

    @computed get tabRouteWithHighestPriority(): Object {
        return this.routeChildren.reduce((prioritizedRoute, route) => {
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

    @computed get routeChildren() {
        const {route, routeChildren} = this.props;

        return routeChildren || route.children;
    }

    @computed get sortedTabRoutes(): Array<Route> {
        return this.routeChildren.concat()
            .sort((childRoute1, childRoute2) => {
                const {tabOrder: tabOrder1 = 0} = childRoute1.options;
                const {tabOrder: tabOrder2 = 0} = childRoute2.options;

                return tabOrder1 - tabOrder2;
            });
    }

    redirectToRouteWithHighestPriority = (): void => {
        const {route, router} = this.props;

        if (!route.children.includes(router.route) && router.route !== route) {
            return;
        }

        if (this.sortedTabRoutes.includes(router.route)) {
            return;
        }

        if (!this.tabRouteWithHighestPriority) {
            return;
        }

        router.redirect(this.tabRouteWithHighestPriority.name, router.attributes);
    };

    handleSelect = (index: number) => {
        const {route, router} = this.props;

        const {
            options: {
                routerAttributesToBlacklist,
            },
        } = route;

        const filteredAttributes = routerAttributesToBlacklist
            ? Object.keys(router.attributes)
                .filter((key) => !routerAttributesToBlacklist.includes(key))
                .reduce((attributes, key) => {
                    attributes[key] = router.attributes[key];
                    return attributes;
                }, {})
            : router.attributes;

        router.navigate(this.sortedTabRoutes[index].name, filteredAttributes);
    };

    render() {
        const {children, childrenProps, header, router, selectedIndex, title, isRootView} = this.props;

        const childComponent = children ? children(childrenProps) : null;

        const selectedTabIndex = selectedIndex !== undefined
            ? selectedIndex
            : childComponent
                ? this.sortedTabRoutes.findIndex((childRoute) => childRoute === childComponent.props.route)
                : undefined;

        const disableGap = selectedTabIndex !== undefined
            ? this.sortedTabRoutes[selectedTabIndex]?.options?.disableTabGap
            : false;

        const showTabs = isRootView || this.sortedTabRoutes.length > 1;
        const type = isRootView ? 'root' : 'nested';

        const className = classNames(
            tabsStyles.tabsContainer,
            tabsStyles[type],
            {
                [tabsStyles.disableGap]: disableGap && !isRootView,
            }
        );

        return (
            <Fragment>
                {title && <h1>{title}</h1>}
                <div className={className}>
                    {showTabs &&
                        <TabsComponent
                            onSelect={this.handleSelect}
                            selectedIndex={selectedTabIndex}
                            type={type}
                        >
                            {this.sortedTabRoutes.map((tabRoute) => {
                                const tabTitle = tabRoute.options.tabTitle;
                                const tabBadges = tabRoute.options.tabBadges || [];

                                const badges = (Object.values(tabBadges): any).map(
                                    (badge: BadgeOptions, index: number) => {
                                        if (typeof badge !== 'object') {
                                            throw new Error(
                                                `The value of a badge entry must be an object,
                                            but ${typeof badge} was given!`
                                            );
                                        }

                                        return (
                                            <Badge
                                                dataPath={badge.dataPath}
                                                key={index}
                                                requestParameters={badge.requestParameters}
                                                routeName={badge.routeName}
                                                router={router}
                                                routerAttributesToRequest={badge.routerAttributesToRequest}
                                                visibleCondition={badge.visibleCondition}
                                            />
                                        );
                                    });

                                return (
                                    <TabsComponent.Tab badges={badges} key={tabRoute.name} type={type}>
                                        {tabTitle ? translate(tabTitle) : tabRoute.name}
                                    </TabsComponent.Tab>
                                );
                            })}
                        </TabsComponent>
                    }
                </div>
                {header}
                {childComponent}
            </Fragment>
        );
    }
}

export default Tabs;
