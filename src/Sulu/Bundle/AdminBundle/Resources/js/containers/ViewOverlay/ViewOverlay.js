// @flow
import React from 'react';
import {computed, toJS} from 'mobx';
import {observer} from 'mobx-react';
import {createMemoryHistory} from 'history';
import Overlay from '../../components/Overlay';
import Router, {getViewKeyFromRoute, Route} from '../../services/Router';
import {translate} from '../../utils/Translator';
import Toolbar, {ToolbarSnackbars} from '../Toolbar';
import toolbarStorePool from '../Toolbar/stores/toolbarStorePool';
import ViewRenderer, {updateRouterAttributesFromView} from '../ViewRenderer';
import type {Size} from '../../components/Overlay/types';
import type {AttributeMap, UpdateRouteMethod} from '../../services/Router/types';

type Props = {|
    attributes: AttributeMap,
    onClose: () => void,
    parentRouter: Router,
    route: string,
    size?: Size,
    title: string,
|};

// the priority of the form dirty warning, so it runs before ViewRenderer and withToolbar react to the navigation
const PARENT_ROUTE_HOOK_PRIORITY = 2048;

// Closing runs the update route hooks with this route, so a view can veto it as it vetoes a navigation away,
// e.g. the form with its dirty warning, which then calls the passed method to close after all.
const CLOSE_ROUTE = new Route({name: 'sulu_admin.view_overlay_close', path: '', type: ''});

// the keys ViewRenderer gives a view and its parents, the view is mounted anew if one of them changes
function getViewKeys(route: ?Route, attributes: ?AttributeMap): string {
    return route ? getViewKeys(route.parent, attributes) + '/' + String(getViewKeyFromRoute(route, attributes)) : '';
}

// Overlays are modal, so each nesting depth has one open at a time and reuses the toolbar store of that depth.
let openOverlayCount = 0;

@observer
class ViewOverlay extends React.Component<Props> {
    router: Router;
    toolbarStoreKey: string;
    parentRouteHookDisposer: () => void;
    leavingParentRoute: boolean = false;

    constructor(props: Props) {
        super(props);

        const {attributes, route} = this.props;

        openOverlayCount++;
        this.toolbarStoreKey = 'sulu_admin.view_overlay_' + openOverlayCount;
        if (!toolbarStorePool.hasStore(this.toolbarStoreKey)) {
            toolbarStorePool.createStore(this.toolbarStoreKey);
        }

        this.router = new Router(createMemoryHistory());
        this.router.addUpdateAttributesHook(updateRouterAttributesFromView);
        this.router.addUpdateAttributesHook(this.getLocaleAttributes);
        this.router.addUpdateRouteHook(this.closeOnNavigationToBackView);
        this.router.navigate(route, attributes);

        this.parentRouteHookDisposer = this.props.parentRouter.addUpdateRouteHook(
            this.checkViewsBeforeParentNavigation,
            PARENT_ROUTE_HOOK_PRIORITY
        );
    }

    // The main router fills in an omitted locale from the content locale of the user. The overlay has its own
    // locale, so views navigating without one (e.g. after creating a copy) keep the one of the overlay.
    getLocaleAttributes = (route: Route): AttributeMap => {
        const locale = this.router.attributes && this.router.attributes.locale;
        const locales = toJS(route.options.locales);

        if (!locale || !route.availableAttributes.includes('locale') || (locales && !locales.includes(locale))) {
            return {};
        }

        return {locale};
    };

    // Actions like delete leave the form for its list with the back view. The overlay has no place for the list,
    // so it closes instead.
    closeOnNavigationToBackView = (newRoute: ?Route): boolean => {
        const {route} = this.router;

        if (route && newRoute && route.options.backView && newRoute.name === route.options.backView) {
            this.props.onClose();

            return false;
        }

        return true;
    };

    // A navigation of the parent router, e.g. the back button of the browser, removes the view the overlay is part
    // of and the overlay with it, so the views of the overlay have to agree as they do for closing it.
    checkViewsBeforeParentNavigation = (
        newRoute: ?Route,
        newAttributes: ?AttributeMap,
        updateRouteMethod: ?UpdateRouteMethod
    ): boolean => {
        const {parentRouter} = this.props;

        // without a route the window is closed, which the own router of the overlay listens to itself
        if (this.leavingParentRoute || !newRoute || !updateRouteMethod) {
            return true;
        }

        // the tabs around the view reload for another id and remove it while they do so
        const keepsView = getViewKeys(newRoute, newAttributes)
            === getViewKeys(parentRouter.route, parentRouter.attributes)
            && newAttributes?.id === parentRouter.attributes.id;

        if (keepsView) {
            return true;
        }

        return this.runCloseHooks(() => {
            this.leavingParentRoute = true;
            updateRouteMethod(newRoute.name, newAttributes || {});
            this.leavingParentRoute = false;
        });
    };

    runCloseHooks(close: () => void): boolean {
        return this.router.sortedUpdateRouteHooks.every((hook) => hook(CLOSE_ROUTE, {}, close));
    }

    componentWillUnmount() {
        this.parentRouteHookDisposer();
        this.router.destroy();
        openOverlayCount--;
    }

    @computed get closeSuccessMessage(): string | void {
        return toolbarStorePool.getStore(this.toolbarStoreKey).showSuccess
            ? translate('sulu_admin.success')
            : undefined;
    }

    handleClose = () => {
        const {onClose} = this.props;
        const closeOverlay = () => onClose();

        if (this.runCloseHooks(closeOverlay)) {
            onClose();
        }
    };

    render() {
        const {size, title} = this.props;
        const toolbarStore = toolbarStorePool.getStore(this.toolbarStoreKey);

        return (
            <Overlay
                closeSuccessMessage={this.closeSuccessMessage}
                onClose={this.handleClose}
                open={true}
                size={size}
                snackbar={<ToolbarSnackbars toolbarStore={toolbarStore} />}
                title={title}
                toolbar={<Toolbar hideBackButton={true} hideSnackbars={true} storeKey={this.toolbarStoreKey} />}
            >
                {!!this.router.route &&
                    <ViewRenderer
                        router={this.router}
                        sidebarAvailable={false}
                        toolbarStoreKey={this.toolbarStoreKey}
                    />
                }
            </Overlay>
        );
    }
}

export default ViewOverlay;
