// @flow
import React, {Fragment} from 'react';
import type {Node} from 'react';
import {autorun, computed} from 'mobx';
import {observer} from 'mobx-react';
import TabsComponent from '../../components/Tabs';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {Route} from '../../services/Router';
import {translate} from '../../utils/Translator';
import tabsStyles from './tabs.scss';

type Props = ViewProps & {
    childrenProps: *,
    header?: Node,
    routeChildren?: Array<Route>,
    selectedIndex?: number,
};

@observer
class Tabs extends React.Component<Props> {
    static defaultProps = {
        childrenProps: {},
    };

    redirectToRouteWithHighestPriorityDisposer: () => void;

    constructor(props: Props) {
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
        const {router} = this.props;
        router.navigate(this.sortedTabRoutes[index].name, router.attributes);
    };

    render() {
        const {children, childrenProps, header, selectedIndex} = this.props;

        const childComponent = children ? children(childrenProps) : null;

        const selectedTabIndex = selectedIndex !== undefined
            ? selectedIndex
            : childComponent
                ? this.sortedTabRoutes.findIndex((childRoute) => childRoute === childComponent.props.route)
                : undefined;

        return (
            <Fragment>
                <div className={tabsStyles.tabsContainer}>
                    <TabsComponent onSelect={this.handleSelect} selectedIndex={selectedTabIndex}>
                        {this.sortedTabRoutes.map((tabRoute) => {
                            const tabTitle = tabRoute.options.tabTitle;
                            return (
                                <TabsComponent.Tab key={tabRoute.name}>
                                    {tabTitle ? translate(tabTitle) : tabRoute.name}
                                </TabsComponent.Tab>
                            );
                        })}
                    </TabsComponent>
                </div>
                {header}
                {childComponent}
            </Fragment>
        );
    }
}

export default Tabs;
