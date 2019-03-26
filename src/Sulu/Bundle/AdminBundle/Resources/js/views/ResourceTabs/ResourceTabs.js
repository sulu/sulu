// @flow
import React, {Fragment} from 'react';
import {computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import Loader from '../../components/Loader';
import type {Route} from '../../services/Router';
import Tabs from '../../views/Tabs';
import type {ViewProps} from '../../containers/ViewRenderer';
import ResourceStore from '../../stores/ResourceStore';
import resourceTabsStyles from './resourceTabs.scss';

type Props = ViewProps & {
    locales?: Array<string>,
    titleProperty?: string,
};

@observer
export default class ResourceTabs extends React.Component<Props> {
    resourceStore: ResourceStore;

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

        router.addUpdateRouteHook(this.reloadResourceStoreOnRouteChange);
    }

    reloadResourceStoreOnRouteChange = (route: ?Route) => {
        const {router, route: viewRoute} = this.props;

        if (router.route === viewRoute) {
            return true;
        }

        if (viewRoute.children.includes(route)) {
            this.resourceStore.reload();
        }

        return true;
    };

    componentWillUnmount() {
        const {router} = this.props;

        this.resourceStore.destroy();
        router.removeUpdateRouteHook(this.reloadResourceStoreOnRouteChange);
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

    @computed get sortedTabRoutes(): Array<Object> {
        const {route} = this.props;

        return route.children.concat()
            .sort((childRoute1, childRoute2) => {
                const {tabOrder: tabOrder1 = 0} = childRoute1.options;
                const {tabOrder: tabOrder2 = 0} = childRoute2.options;

                return tabOrder1 - tabOrder2;
            });
    }

    @computed get visibleTabRoutes(): Array<Object> {
        const data = toJS(this.resourceStore.data);

        return this.sortedTabRoutes
            .filter((childRoute) => {
                const {
                    options: {
                        tabCondition,
                    },
                } = childRoute;

                return !tabCondition || jexl.evalSync(tabCondition, data);
            });
    }

    render() {
        const {children} = this.props;

        const childComponent = children
            ? children({locales: this.locales, resourceStore: this.resourceStore})
            : null;

        const selectedRouteIndex = childComponent
            ? this.visibleTabRoutes.findIndex((childRoute) => childRoute === childComponent.props.route)
            : undefined;

        const selectedRoute = selectedRouteIndex !== undefined ? this.visibleTabRoutes[selectedRouteIndex] : undefined;

        return this.resourceStore.initialized
            ? (
                <Tabs {...this.props} routeChildren={this.visibleTabRoutes} selectedIndex={selectedRouteIndex}>
                    {() => (
                        <Fragment>
                            {this.sortedTabRoutes[0] !== selectedRoute && this.title &&
                                <h1>{this.title}</h1>
                            }
                            {childComponent}
                        </Fragment>
                    )}
                </Tabs>
            )
            : (
                <div className={resourceTabsStyles.loader}>
                    <Loader />
                </div>
            );
    }
}
