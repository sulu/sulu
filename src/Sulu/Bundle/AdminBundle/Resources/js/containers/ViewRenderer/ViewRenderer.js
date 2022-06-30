// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {observable, reaction} from 'mobx';
import Router, {getViewKeyFromRoute, Route} from '../../services/Router';
import userStore from '../../stores/userStore';
import View from '../../components/View';
import viewRegistry from './registries/viewRegistry';
import type {Element} from 'react';

type Props = {
    router: Router,
};

const UPDATE_ROUTE_HOOK_PRIORITY = 1024;

@observer
class ViewRenderer extends React.Component<Props> {
    @observable loginCount: number = 0;

    updateLoginCountDisposer: ?() => *;

    componentDidMount() {
        const {router} = this.props;

        router.addUpdateRouteHook((newRoute, newAttributes) => {
            const {attributes: oldAttributes, route: oldRoute} = router;
            if (getViewKeyFromRoute(newRoute, newAttributes) !== getViewKeyFromRoute(oldRoute, oldAttributes)) {
                router.clearBindings();
            }

            return true;
        }, UPDATE_ROUTE_HOOK_PRIORITY);

        this.updateLoginCountDisposer = reaction(
            () => (userStore.loggedIn),
            (newIsLoggedIn) => {
                if (newIsLoggedIn) {
                    this.loginCount = this.loginCount + 1;
                }
            }
        );
    }

    componentWillUnmount() {
        if (this.updateLoginCountDisposer) {
            this.updateLoginCountDisposer();
        }
    }

    renderView(route: Route, child: Element<*> | null = null) {
        const {router} = this.props;
        const CurrentView = viewRegistry.get(route.type);
        const viewConfig = viewRegistry.getConfig(route.type);

        let viewKey = getViewKeyFromRoute(route, router.attributes) || '';
        if (CurrentView.remountViewOnLogin) {
            viewKey = viewKey + '__' + this.loginCount;
        }

        const element = (
            <CurrentView
                isRootView={!route.parent}
                key={viewKey}
                route={route}
                router={router}
            >
                {(props) => child ? React.cloneElement(child, props) : null}
            </CurrentView>
        );

        if (!route.parent) {
            if (!viewConfig.rootSpaceless) {
                return (
                    <View>
                        {element}
                    </View>
                );
            }

            return element;
        }

        return this.renderView(route.parent, element);
    }

    render() {
        return this.renderView(this.props.router.route);
    }
}

export default ViewRenderer;
