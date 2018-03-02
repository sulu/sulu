// @flow
import React, {Fragment} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {MultiItemSelection} from '../../components';
import DatagridOverlay from './DatagridOverlay';

type Props = {
    icon: string,
    resourceKey: string,
    title: string,
};

@observer
export default class Assignment extends React.Component<Props> {
    static defaultProps = {
        icon: 'su-plus',
        resourceKey: 'snippets', // TODO remove, only here for testing purposes
        title: 'Assignment', // TODO remove, only here for testing purposes
    };

    @observable overlayOpen: boolean = false;

    @action closeOverlay() {
        this.overlayOpen = false;
    }

    @action openOverlay() {
        this.overlayOpen = true;
    }

    @action handleOverlayOpen = () => {
        this.openOverlay();
    };

    @action handleOverlayClose = () => {
        this.closeOverlay();
    };

    handleOverlayConfirm = () => {
        this.closeOverlay();
    };

    render() {
        const {icon, resourceKey, title} = this.props;

        return (
            <Fragment>
                <MultiItemSelection
                    leftButton={{
                        icon,
                        onClick: this.handleOverlayOpen,
                    }}
                />
                <DatagridOverlay
                    onClose={this.handleOverlayClose}
                    onConfirm={this.handleOverlayConfirm}
                    open={this.overlayOpen}
                    resourceKey={resourceKey}
                    title={title}
                />
            </Fragment>
        );
    }
}
