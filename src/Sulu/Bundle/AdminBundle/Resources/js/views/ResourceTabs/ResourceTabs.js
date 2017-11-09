// @flow
import React from 'react';
import Tabs from '../../components/Tabs';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../services/Translator';
import ResourceStore from '../../stores/ResourceStore';

export default class ResourceTabs extends React.PureComponent<ViewProps> {
    resourceStore: ResourceStore;

    componentWillMount() {
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
        this.resourceStore = new ResourceStore(resourceKey, id);
    }

    componentWillUnmount() {
        this.resourceStore.destroy();
    }

    handleSelect = (index: number) => {
        const {router, route} = this.props;
        router.navigate(route.children[index].name, router.attributes);
    };

    render() {
        const {children, route} = this.props;
        const ChildComponent = children ? children({resourceStore: this.resourceStore}) : null;

        const selectedRouteIndex = ChildComponent
            ? route.children.findIndex((childRoute) => childRoute === ChildComponent.props.route)
            : undefined;

        return (
            <div>
                <Tabs selectedIndex={selectedRouteIndex} onSelect={this.handleSelect}>
                    {route.children.map((childRoute) => {
                        const tabTitle = childRoute.options.tabTitle;
                        return (
                            <Tabs.Tab key={childRoute.name}>
                                {tabTitle ? translate(tabTitle) : childRoute.name}
                            </Tabs.Tab>
                        );
                    })}
                </Tabs>
                {ChildComponent}
            </div>
        );
    }
}
