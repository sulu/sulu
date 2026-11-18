// @flow
import React from 'react';
import {computed, toJS} from 'mobx';
import {observer} from 'mobx-react';
import {createMemoryHistory} from 'history';
import Overlay from '../../components/Overlay';
import Router, {Route} from '../../services/Router';
import {translate} from '../../utils/Translator';
import Toolbar, {ToolbarSnackbars} from '../Toolbar';
import toolbarStorePool from '../Toolbar/stores/toolbarStorePool';
import ViewRenderer, {updateRouterAttributesFromView} from '../ViewRenderer';
import type {Size} from '../../components/Overlay/types';
import type {AttributeMap} from '../../services/Router/types';

type Props = {|
    attributes: AttributeMap,
    onClose: () => void,
    route: string,
    size?: Size,
    title: string,
|};

// Closing runs the update route hooks with this route, so a view can veto it as it vetoes a navigation away,
// e.g. the form with its dirty warning, which then calls the passed method to close after all.
const CLOSE_ROUTE = new Route({name: 'sulu_admin.view_overlay_close', path: '', type: ''});

// Overlays are modal, so each nesting depth has one open at a time and reuses the toolbar store of that depth.
let openOverlayCount = 0;

@observer
class ViewOverlay extends React.Component<Props> {
    router: Router;
    toolbarStoreKey: string;

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

    componentWillUnmount() {
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

        if (this.router.sortedUpdateRouteHooks.every((hook) => hook(CLOSE_ROUTE, {}, closeOverlay))) {
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
