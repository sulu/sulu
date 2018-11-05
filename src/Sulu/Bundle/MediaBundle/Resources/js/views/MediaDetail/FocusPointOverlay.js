// @flow
import React from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Overlay} from 'sulu-admin-bundle/components';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import ImageFocusPoint from '../../components/ImageFocusPoint';
import type {Point} from '../../components/ImageFocusPoint';

type Props = {|
    onClose: () => void,
    open: boolean,
    resourceStore: ResourceStore,
|};

@observer
export default class FocusPointOverlay extends React.Component<Props> {
    @observable focusPointX: number;
    @observable focusPointY: number;
    resourceStore: ResourceStore;

    constructor(props: Props) {
        super(props);

        this.resourceStore = this.props.resourceStore.clone();
        this.updateFocusPoint();
    }

    componentDidUpdate(prevProps: Props) {
        if (!prevProps.open && this.props.open) {
            this.resourceStore = this.props.resourceStore.clone();
            this.updateFocusPoint();
        }

        if (prevProps.open && !this.props.open) {
            this.resourceStore.destroy();
        }
    }

    @action updateFocusPoint = () => {
        const {resourceStore} = this.props;
        const {focusPointX = 1, focusPointY = 1} = resourceStore.data;

        this.focusPointX = focusPointX;
        this.focusPointY = focusPointY;
    };

    handleClose = () => {
        this.props.onClose();
    };

    handleConfirm = () => {
        this.resourceStore.change('focusPointX', this.focusPointX);
        this.resourceStore.change('focusPointY', this.focusPointY);

        this.resourceStore.save().then(() => {
            this.props.resourceStore.set('focusPointX', this.focusPointX);
            this.props.resourceStore.set('focusPointY', this.focusPointY);
            this.props.onClose();
        });
    };

    @action handleFocusPointChange = (point: Point) => {
        this.focusPointX = point.x;
        this.focusPointY = point.y;
    };

    render() {
        const {open} = this.props;

        return (
            <Overlay
                confirmLoading={this.resourceStore.saving}
                confirmText={translate('sulu_admin.save')}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={open}
                title={translate('sulu_media.set_focus_point')}
            >
                <ImageFocusPoint
                    image={this.resourceStore.data.url}
                    onChange={this.handleFocusPointChange}
                    value={{x: this.focusPointX, y: this.focusPointY}}
                />
            </Overlay>
        );
    }
}
