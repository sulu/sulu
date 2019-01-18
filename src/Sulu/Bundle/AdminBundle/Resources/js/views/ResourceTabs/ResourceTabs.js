// @flow
import React, {Fragment} from 'react';
import {action, autorun, computed, observable, toJS} from 'mobx';
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

    @observable visibleTabIndices: Array<number> = [];
    redirectToRouteWithHighestPriorityDisposer: () => void;
    visibleTabIndicesDisposer: () => void;

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

        this.visibleTabIndicesDisposer = autorun(this.updateVisibleTabIndices);
        this.redirectToRouteWithHighestPriorityDisposer = autorun(this.redirectToRouteWithHighestPriority);
    }

    componentWillUnmount() {
        this.visibleTabIndicesDisposer();
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

        return this.visibleTabIndices.map((index) => {
            return route.children[index];
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

    updateVisibleTabIndices = () => {
        const data = toJS(this.resourceStore.data);
        if (this.resourceStore.loading || this.resourceStore.saving) {
            return;
        }

        const {route} = this.props;

        const tabConditionPromises = route.children
            .map((childRoute) => {
                const {tabCondition} = childRoute.options;

                if (!tabCondition) {
                    return Promise.resolve(true);
                }

                return jexl.eval(childRoute.options.tabCondition, data);
            });

        Promise.all(tabConditionPromises).then(action((tabConditionResults) => {
            this.visibleTabIndices = tabConditionResults
                .map((tabConditionResult, index) => tabConditionResult ? index : undefined)
                .filter((tabConditionIndex) => tabConditionIndex !== undefined)
                .sort((index1, index2) => {
                    const {tabOrder: tabOrder1 = 0} = route.children[index1].options;
                    const {tabOrder: tabOrder2 = 0} = route.children[index2].options;

                    return tabOrder1 - tabOrder2;
                });
        }));
    };

    handleSelect = (index: number) => {
        const {router} = this.props;
        router.navigate(this.visibleTabRoutes[index].name, router.attributes);

        // TODO replace by asking for confirmation when changing tabs and reload only if dirty data should be deleted
        this.resourceStore.load();
    };

    render() {
        const {children} = this.props;

        const ChildComponent = children ? children({locales: this.locales, resourceStore: this.resourceStore}) : null;

        const selectedRouteIndex = ChildComponent
            ? this.visibleTabRoutes.findIndex((childRoute) => childRoute === ChildComponent.props.route)
            : undefined;

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
                    {selectedRouteIndex !== 0 && <h1>{this.title}</h1>}
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
