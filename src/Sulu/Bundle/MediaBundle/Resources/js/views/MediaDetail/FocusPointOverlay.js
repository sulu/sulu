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
    @observable focusPointX: number = 1;
    @observable focusPointY: number = 1;

    constructor(props: Props) {
        super(props);

        this.updateFocusPoint();
    }

    componentDidUpdate(prevProps: Props) {
        if (!prevProps.open && this.props.open) {
            this.updateFocusPoint();
        }
    }

    @action updateFocusPoint = () => {
        const {resourceStore} = this.props;
        const {focusPointX, focusPointY} = resourceStore.data;

        this.focusPointX = focusPointX;
        this.focusPointY = focusPointY;
    };

    handleClose = () => {
        this.props.onClose();
    };

    handleConfirm = () => {
        const {resourceStore} = this.props;

        resourceStore.change('focusPointX', this.focusPointX);
        resourceStore.change('focusPointY', this.focusPointY);

        this.props.resourceStore.save().then(() => {
            this.props.onClose();
        });
    };

    @action handleFocusPointChange = (point: Point) => {
        this.focusPointX = point.x;
        this.focusPointY = point.y;
    };

    render() {
        const {open, resourceStore} = this.props;

        return (
            <Overlay
                confirmLoading={resourceStore.saving}
                confirmText={translate('sulu_admin.confirm')}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={open}
                title={translate('sulu_media.set_focus_point')}
            >
                <ImageFocusPoint
                    image={resourceStore.data.url}
                    onChange={this.handleFocusPointChange}
                    value={{x: this.focusPointX, y: this.focusPointY}}
                />
            </Overlay>
        );
    }
}
