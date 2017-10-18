// @flow
import React from 'react';
import Tabs from '../../components/Tabs';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../services/Translator';

export default class ResourceTabs extends React.PureComponent<ViewProps> {
    handleSelect = (index: number) => {
        const {router, route} = this.props;
        router.navigate(route.children[index].name, router.attributes, router.query);
    };

    render() {
        const {children, route} = this.props;

        const selectedRouteIndex = children
            ? route.children.findIndex((childRoute) => childRoute === children.props.route)
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
                {this.props.children}
            </div>
        );
    }
}
