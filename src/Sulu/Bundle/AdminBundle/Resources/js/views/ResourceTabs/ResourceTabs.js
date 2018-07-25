// @flow
import React, {Fragment} from 'react';
import {computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import Tabs from '../../components/Tabs';
import Loader from '../../components/Loader';
import type {ViewProps} from '../../containers/ViewRenderer';
import {translate} from '../../utils/Translator';
import ResourceStore from '../../stores/ResourceStore';
import resourceTabsStyle from './resourceTabs.scss';

type Props = ViewProps & {
    loading: boolean,
    locales?: Array<string>,
};

@observer
export default class ResourceTabs extends React.Component<Props> {
    resourceStore: ResourceStore;

    static defaultProps = {
        loading: false,
    };

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
        const {children, loading, route} = this.props;

        if (loading || this.resourceStore.loading) {
            return (
                <div className={resourceTabsStyle.loader}>
                    <Loader />
                </div>
            );
        }

        const ChildComponent = children ? children({locales: this.locales, resourceStore: this.resourceStore}) : null;

        const selectedRouteIndex = ChildComponent
            ? route.children.findIndex((childRoute) => childRoute === ChildComponent.props.route)
            : undefined;

        return (
            <Fragment>
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
            </Fragment>
        );
    }
}
