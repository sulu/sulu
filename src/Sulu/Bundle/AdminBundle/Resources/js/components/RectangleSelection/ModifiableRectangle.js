// @flow
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import React from 'react';
import type {RectangleChange} from './types';
import modifiableRectangleStyles from './modifiableRectangle.scss';

type Props = {
    left: number,
    top: number,
    width: number,
    height: number,
    backdropSize: number,
    onChange?: (r: RectangleChange) => void,
    onDoubleClick?: () => void,
};

@observer
export default class ModifiableRectangle extends React.Component<Props> {
    static defaultProps = {
        left: 0,
        top: 0,
        backdropSize: 0,
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
        const style = {
            left: this.props.left + 'px',
            top: this.props.top + 'px',
            width: this.props.width + 'px',
            height: this.props.height + 'px',
        };

        return (
            <div
                className={modifiableRectangleStyles.rectangle}
                onDoubleClick={this.handleDoubleClick}
                onMouseDown={this.handleMoveMouseDown}
                style={style}
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
        );
    }
}
