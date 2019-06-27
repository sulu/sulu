// @flow
import React from 'react';
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
class ResourceTabs extends React.Component<Props> {
    resourceStore: ResourceStore;
    reloadResourceStoreOnRouteChangeDisposer: () => void;

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

        this.reloadResourceStoreOnRouteChangeDisposer = router.addUpdateRouteHook(
            this.reloadResourceStoreOnRouteChange
        );
    }

    reloadResourceStoreOnRouteChange = (route: ?Route) => {
        const {router, route: viewRoute} = this.props;

        if (router.route === viewRoute || router.route === route) {
            return true;
        }

        if (viewRoute.children.includes(route) || viewRoute === route) {
            this.resourceStore.reload();
        }

        return true;
    };

    componentWillUnmount() {
        this.resourceStore.destroy();
        this.reloadResourceStoreOnRouteChangeDisposer();
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

        if (!this.resourceStore.initialized && this.resourceStore.loading) {
            return undefined;
        }

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
            ? children({locales: this.locales, resourceStore: this.resourceStore, title: this.title})
            : null;

        const selectedRouteIndex = childComponent
            ? this.visibleTabRoutes.findIndex((childRoute) => childRoute === childComponent.props.route)
            : undefined;

        return this.resourceStore.initialized
            ? (
                <Tabs {...this.props} routeChildren={this.visibleTabRoutes} selectedIndex={selectedRouteIndex}>
                    {() => childComponent}
                </Tabs>
            )
            : (
                <div className={resourceTabsStyles.loader}>
                    <Loader />
                </div>
            );
    }
}

export default ResourceTabs;
