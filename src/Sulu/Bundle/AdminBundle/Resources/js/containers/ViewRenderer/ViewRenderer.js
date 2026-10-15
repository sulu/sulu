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
    @observable userChangeCount: number = 0;

    lastUserId: ?number;

    updateLoginCountDisposer: ?() => *;
    updateUserChangeCountDisposer: ?() => *;

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

        this.lastUserId = userStore.user ? userStore.user.id : undefined;
        this.updateUserChangeCountDisposer = reaction(
            () => (userStore.user ? userStore.user.id : undefined),
            (newUserId) => {
                if (newUserId === undefined) {
                    return;
                }

                if (this.lastUserId !== undefined && this.lastUserId !== newUserId) {
                    this.userChangeCount = this.userChangeCount + 1;
                }

                this.lastUserId = newUserId;
            }
        );
    }

    componentWillUnmount() {
        if (this.updateLoginCountDisposer) {
            this.updateLoginCountDisposer();
        }

        if (this.updateUserChangeCountDisposer) {
            this.updateUserChangeCountDisposer();
        }
    }

    renderView(route: Route, child: Element<*> | null = null) {
        const {router} = this.props;
        const CurrentView = viewRegistry.get(route.type);
        const viewConfig = viewRegistry.getConfig(route.type);

        // the data of a view, permissions included, belongs to the user who loaded it
        let viewKey = (getViewKeyFromRoute(route, router.attributes) || '') + '__user' + this.userChangeCount;
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
            if (!viewConfig.disableDefaultSpacing) {
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
