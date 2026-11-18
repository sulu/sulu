// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import {createMemoryHistory} from 'history';
import Overlay from '../../components/Overlay';
import Router, {Route} from '../../services/Router';
import {translate} from '../../utils/Translator';
import Toolbar, {ToolbarSnackbars} from '../Toolbar';
import toolbarStorePool from '../Toolbar/stores/toolbarStorePool';
import ViewRenderer from '../ViewRenderer';
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
        this.router.navigate(route, attributes);
    }

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
