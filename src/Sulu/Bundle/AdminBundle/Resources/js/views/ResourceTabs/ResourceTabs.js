// @flow
import React, {Fragment} from 'react';
import {computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Tabs from '../../components/Tabs';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../utils/Translator';
import {withSidebar} from '../../containers/Sidebar';
import ResourceStore from '../../stores/ResourceStore';

type Props = ViewProps & {
    locales?: Array<string>,
};

@observer
class ResourceTabs extends React.Component<Props> {
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

        const options = {};
        if (this.locales) {
            options.locale = observable.box();
        }

        this.resourceStore = new ResourceStore(resourceKey, id, options);
    }

    componentWillUnmount() {
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
            </Fragment>
        );
    }
}

export default withSidebar(ResourceTabs, function() {
    const {
        router: {
            route: {
                options: {
                    preview,
                },
            },
        },
    } = this.props;

    if (!preview) {
        return {};
    }

    return {
        view: 'sulu_preview.preview',
        sizes: ['medium', 'large'],
        props: {
            router: this.props.router,
            resourceStore: this.resourceStore,
        },
    };
});
