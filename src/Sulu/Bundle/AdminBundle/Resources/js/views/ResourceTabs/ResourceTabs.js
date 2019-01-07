// @flow
import React, {Fragment} from 'react';
import {action, autorun, computed, observable, toJS} from 'mobx';
import {observer} from 'mobx-react';
import jexl from 'jexl';
import Tabs from '../../components/Tabs';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../utils/Translator';
import ResourceStore from '../../stores/ResourceStore';

type Props = ViewProps & {
    locales?: Array<string>,
};

@observer
export default class ResourceTabs extends React.Component<Props> {
    resourceStore: ResourceStore;

    @observable visibleTabIndices: Array<number> = [];
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
        }

        this.resourceStore = new ResourceStore(resourceKey, id, options);

        this.visibleTabIndicesDisposer = autorun(this.updateVisibleTabIndices);
    }

    componentDidMount() {
        const {route, router} = this.props;

        if (route === router.route && route.children.length !== 0) {
            router.redirect(route.children[0].name, router.attributes);
        }
    }

    componentWillUnmount() {
        this.visibleTabIndicesDisposer();
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
        const {router, route} = this.props;
        router.navigate(route.children[index].name, router.attributes);
    };

    render() {
        const {children, route} = this.props;

        const ChildComponent = children ? children({locales: this.locales, resourceStore: this.resourceStore}) : null;

        const selectedRouteIndex = ChildComponent
            ? route.children.findIndex((childRoute) => childRoute === ChildComponent.props.route)
            : undefined;

        return (
            <Fragment>
                <Tabs onSelect={this.handleSelect} selectedIndex={selectedRouteIndex}>
                    {route.children && this.visibleTabIndices.map((index) => {
                        const childRoute = route.children[index];
                        const tabTitle = childRoute.options.tabTitle;
                        return (
                            <Tabs.Tab key={childRoute.name}>
                                {tabTitle ? translate(tabTitle) : childRoute.name}
                            </Tabs.Tab>
                        );
                    })}
                </Tabs>
                {ChildComponent}
            </Fragment>
        );
    }
}
