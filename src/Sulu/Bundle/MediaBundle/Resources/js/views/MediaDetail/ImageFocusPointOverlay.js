// @flow
import React from 'react';
import {action, computed} from 'mobx';
import {observer} from 'mobx-react';
import {translate} from 'sulu-admin-bundle/utils';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {Overlay} from 'sulu-admin-bundle/components';
import ImageFocusPoint from '../../components/ImageFocusPoint';
import type {Point} from '../../components/ImageFocusPoint';
import imageFocusPointOverlayStyles from './imageFocusPointOverlay.scss';

type Props = {
    open: boolean,
    onClose: () => void,
    resourceStore: ResourceStore,
};

@observer
export default class ImageFocusPointOverlay extends React.PureComponent<Props> {
    static defaultProps = {
        open: false,
    };

    @computed get image(): string {
        const {resourceStore} = this.props;

        return resourceStore.data.url;
    }

    @computed get focusPoint(): Point {
        const {
            resourceStore: {
                data: {
                    focusPointX,
                    focusPointY,
                },
            },
        } = this.props;

        return {
            x: focusPointX,
            y: focusPointY,
        };
    }

    handleClose = () => {
        this.props.onClose();
    };

    handleConfirm = () => {
        this.props.onClose();
    };

    @action handleFocusPointChange = (value: Point) => {
        const {resourceStore} = this.props;

        resourceStore.set('focusPointX', value.x);
        resourceStore.set('focusPointY', value.y);
    };

    render() {
        const {open} = this.props;
        const actions = [{
            title: translate('sulu_admin.cancel'),
            onClick: this.handleClose,
        }];

        return (
            <Overlay
                open={open}
                title={translate('sulu_media.set_focus_point')}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                confirmText={translate('sulu_media.save_and_crop')}
                actions={actions}
            >
                <div className={imageFocusPointOverlayStyles.overlay}>
                    <div className={imageFocusPointOverlayStyles.imageFocusPoint}>
                        <ImageFocusPoint
                            image={this.image}
                            value={this.focusPoint}
                            onChange={this.handleFocusPointChange}
                        />
                    </div>
                </div>
            </Overlay>
        );
    }
}
