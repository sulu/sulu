// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React, {Fragment} from 'react';
import {translate} from '../../utils/Translator';
import type {RectangleChange} from './types';
import modifiableRectangleStyles from './modifiableRectangle.scss';

type Props = {
    left: number,
    top: number,
    width: number,
    height: number,
    backdropSize: number,
    minSizeReached: boolean,
    onChange?: (r: RectangleChange) => void,
    onDoubleClick?: () => void,
};

@observer
export default class ModifiableRectangle extends React.Component<Props> {
    static defaultProps = {
        backdropSize: 0,
        left: 0,
        top: 0,
    };

    @observable clickAnchor = {pageY: 0, pageX: 0};
    @observable moveMode = false;
    @observable resizeMode = false;

    componentDidMount() {
        const body = document.body;
        if (body) {
            body.addEventListener('mouseup', this.handleMouseUp);
            body.addEventListener('mousemove', this.handleMouseMove);
        }
    }

    componentWillUnmount() {
        const body = document.body;
        if (body) {
            body.removeEventListener('mouseup', this.handleMouseUp);
            body.removeEventListener('mousemove', this.handleMouseMove);
        }
    }

    @action setClickAnchor(event: MouseEvent) {
        this.clickAnchor.pageY = event.pageY;
        this.clickAnchor.pageX = event.pageX;
    }

    @action handleMoveMouseDown = (event: MouseEvent) => {
        event.stopPropagation();
        this.setClickAnchor(event);
        this.moveMode = true;
    };

    @action handleResizeMouseDown = (event: MouseEvent) => {
        event.stopPropagation();
        this.setClickAnchor(event);
        this.resizeMode = true;
    };

    @action handleMouseUp = () => {
        this.moveMode = false;
        this.resizeMode = false;
    };

    @action handleMouseMove = (event: MouseEvent) => {
        let top = 0, left = 0, width = 0, height = 0;

        if (this.moveMode) {
            top = event.pageY - this.clickAnchor.pageY;
            left = event.pageX - this.clickAnchor.pageX;
        }

        if (this.resizeMode) {
            height = event.pageY - this.clickAnchor.pageY;
            width = event.pageX - this.clickAnchor.pageX;
        }

        if (this.moveMode || this.resizeMode) {
            this.setClickAnchor(event);
            if (this.props.onChange) {
                this.props.onChange({top, left, width, height});
            }
        }
    };

    handleDoubleClick = this.props.onDoubleClick;

    render() {
        const {height, left, minSizeReached, top, width} = this.props;

        return (
            <Fragment>
                {minSizeReached &&
                    <div
                        className={modifiableRectangleStyles.minSizeNotification}
                        style={{left: left + 'px', top: top + height + 'px', width: width + 'px'}}
                    >
                        {translate('sulu_media.min_size_notification')}
                    </div>
                }
                <div
                    className={modifiableRectangleStyles.rectangle}
                    onDoubleClick={this.handleDoubleClick}
                    onMouseDown={this.handleMoveMouseDown}
                    style={{left: left + 'px', top: top + 'px', width: width + 'px', height: height + 'px'}}
                >
                    <div
                        className={modifiableRectangleStyles.resizeHandle}
                        onMouseDown={this.handleResizeMouseDown}
                    />
                    <div
                        className={modifiableRectangleStyles.backdrop}
                        style={{outlineWidth: this.props.backdropSize + 'px'}}
                    />
                </div>
            </Fragment>
        );
    }
}
